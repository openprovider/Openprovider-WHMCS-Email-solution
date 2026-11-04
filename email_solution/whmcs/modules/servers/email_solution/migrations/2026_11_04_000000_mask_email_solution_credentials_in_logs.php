<?php

use WHMCS\Database\Capsule;
use Illuminate\Database\Migrations\Migration;

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}

/**
 * One-time cleanup (INTGRTNS-598) of credentials logged in plaintext before INTGRTNS-590.
 *
 * TODO: Remove this migration in the next major version. It only cleans up existing data;
 * new log entries are already masked by ApiCall::__curlCall() and email_solution_maskSensitiveParams().
 *
 * Only the credential values are replaced; the rest of each row is left byte-for-byte unchanged.
 * Values that are empty or already masked (all '*') are skipped, so running it again changes nothing.
 *
 * The class name is module-specific because migration classes are global and the SSL module
 * runs its own migrations in the same cron process.
 */
class MaskEmailSolutionCredentialsInLogs extends Migration
{
    const MASK = '********';
    const CHUNK_SIZE = 500;

    public function up()
    {
        $moduleLog = $this->maskLoginRows() + $this->maskRequestRows();

        logActivity("Email Solution: masked credentials in {$moduleLog} Module Log entries (INTGRTNS-598)");
    }

    public function down()
    {
        // Masked values cannot be restored.
    }

    // API login rows: the access token in the response, stored by WHMCS with print_r(): [token] => ..
    private function maskLoginRows()
    {
        $query = Capsule::table('tblmodulelog')
            ->where('module', 'email solution')
            ->where('action', 'generate token')
            ->where('response', 'like', '%[token] => %');

        return $this->maskRows($query, function ($value) {
            return $this->maskPrintR($value, '\s*', ['token']);
        });
    }

    /**
     * Request rows with credentials:
     * - exception handlers that logged the whole $params ([serverpassword], [serveraccesshash], top-level [password])
     * - mailbox "Change Password" API calls ([password_confirmation], the end customer's mailbox password)
     * Also matched on moduletype in case a $params dump was logged under another module name.
     */
    private function maskRequestRows()
    {
        $query = Capsule::table('tblmodulelog')
            ->where(function ($q) {
                $q->where('module', 'email solution')
                    ->orWhere('request', 'like', '%[moduletype] => email_solution%');
            })
            ->where(function ($q) {
                $q->where('request', 'like', '%[serverpassword] => %')
                    ->orWhere('request', 'like', '%[serveraccesshash] => %')
                    ->orWhere('request', 'like', '%[password_confirmation] => %')
                    ->orWhere('request', 'like', '%[password] => %');
            });

        return $this->maskRows($query, function ($value, $column) {
            if ($column !== 'request') {
                return $value;
            }
            $value = $this->maskPrintR($value, '\s*', ['serverpassword', 'serveraccesshash', 'password_confirmation']);
            // Top-level password only; nested [password] keys (e.g. client details hash) are left as is.
            return $this->maskPrintR($value, ' {4}', ['password']);
        });
    }

    /**
     * Walks the matching rows by id and updates only the rows whose request/response changed.
     * Each row is a separate short update, so the table is never locked for long.
     */
    private function maskRows($query, callable $mask)
    {
        $updated = 0;

        $query->select('id', 'request', 'response')->chunkById(self::CHUNK_SIZE, function ($rows) use ($mask, &$updated) {
            foreach ($rows as $row) {
                $changes = [];
                foreach (['request', 'response'] as $column) {
                    $masked = $mask((string) $row->$column, $column);
                    if ($masked !== (string) $row->$column) {
                        $changes[$column] = $masked;
                    }
                }

                if ($changes) {
                    Capsule::table('tblmodulelog')->where('id', $row->id)->update($changes);
                    $updated++;
                }
            }
        });

        return $updated;
    }

    private function maskPrintR($value, $indent, array $keys)
    {
        $pattern = '/^(' . $indent . '\[(?:' . implode('|', $keys) . ')\] => )([^\r\n]*)/m';

        return preg_replace_callback($pattern, function ($m) {
            return $this->isExposed($m[2]) ? $m[1] . self::MASK : $m[0];
        }, $value);
    }

    private function isExposed($value)
    {
        return $value !== '' && !preg_match('/^\*+$/', $value);
    }
}
