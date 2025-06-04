<style>
    #mailcow .modal-body {
        text-align: left;
    }

    #mailcow .modal-body .form-div-section {
        margin: 5px;
    }

    #mailcow .modal-body .row {
        padding: 0px 5px;
    }

    #mailcow .modal-body label {
        margin: 0px !important;
    }

    #mailcow label.error-log {
        color: red;
        font-size: 13px;
        font-style: italic;
        display: none;
    }

    #mailcow .mailbox-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 0 10px;
        border-bottom: 1px solid #ddd;
        margin-bottom: 20px;
    }

    #mailboxAliases th {
        text-align: center;
    }


    .mailbox-header h5 {
        color: #000;
        font-size: 20px;
        font-weight: 500;
    }

    button#mailbox_btn,
    button#mailboxAliases_btn {
        padding: 6px;
        border-radius: 5px;
        background: #336699;
        color: #fff;
        border-color: #336699;
    }

    button.tab-mailbox.active {
        background: #336699e3 !important;
        border-color: #336699e3 !important;
    }

    #mailcow .modal .modal-header {
        background-color: #336699;
        color: #fff;
    }

    td a {
        cursor: pointer;
    }

    .cus_status.active {
        color: green;
        font-weight: bolder;
        text-transform: capitalize;
    }

    span.form-icon-i {
        position: absolute;
        right: 10px;
        top: 20%;
        z-index: 10000;
    }

    span.form-icon-i i {
        color: #6c757d;
    }

    div#mailbox_container {
        padding: 0;
    }

    button#mailbox_btn,
    button#mailboxAliases_btn {
        padding: 6px;
        border-radius: 5px;
        background: #336699;
        color: #fff;
        border-color: #336699;
        border: 1px solid;
    }

    table.dataTable thead tr th,
    table.dataTable tbody tr td {
        text-align: left !important;
    }

    .btn-primary.focus,
    .btn-primary:focus,
    .btn-secondary.focus,
    .btn-secondary:focus {
        box-shadow: unset;
    }


    /* start */

    div#DNSBoxBody {
        padding: 15px;
    }

    .modal button.close span {
        color: #fff !important;
    }

    div#DNSBoxBody p {
        font-size: 14px;
        line-height: 22px;
        color: #000;
    }
    
    div#DNSBoxBody table {
        background: #f5f5f5 !important;
    }
    
    div#DNSBoxBody table tbody tr td {
        padding: 10px;
    }
    
    div#DNSBoxBody table tbody tr td textarea.form-control {
        background: transparent;
        border-color: #ddd;
        font-size: 14px;
        border-radius: 5px;
        min-height: 70px ;
    }
    
    div#DNSBoxBody table tbody tr td.record_type {
        font-size: 14px;
        line-height: 22px;
        color: #000;
    }

    div#DNSBoxBody table tbody tr td.copy_data_td {
        position: relative;
    }
    
    div#DNSBoxBody table tbody tr td.copy_data_td a {
        position: absolute;
        background: unset;
        border: 0;
        padding: 0;
        color: #0606069e;
        right: 29px;
        bottom: 9px;
    }

    table.dataTable tbody th, table.dataTable tbody td {
        padding: 8px 4px;
        font-size: 14px;
    }

    table#mailbox tbody tr td a i {
        font-size: 14px;
    }
 
    .record_type{
        text-align: center;
    }

    .record_type span{
        padding: 5px 10px;
        background-color: #ddd;
        border-radius: 20px;
        margin: 5px;
    }

    div#DNSBox {
    margin-top: 0px;
    }

    div#DNSBox th {
        padding: 10px 0px;
    }
    /* end */
</style>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css">

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.js"></script>


<div id='mailcow'>
    {if $errorMessage['status']}
        {if $errorMessage['status'] == 'success'}
            <div class="alert alert-success">{$errorMessage['message']}</div>
        {/if}

        {if $errorMessage['status'] == 'error'}
            <div class="alert alert-danger">{$errorMessage['message']}</div>
        {/if}
    {/if}
    {if $domainDetail->domain}
        <div class='row' style="padding:20px;" id='mailcow_status'>
            <div class="col-md-8" style="text-align: left;">
                <label><b>Domain:</b> {$domainDetail->domain->name}.{$domainDetail->domain->extension}</label><br>
                <label><b>Status:</b> {if $domainDetail->status == 'active'}<span
                        class="label label-success">Active</span>{else}<span>{$domainDetail->status}</span>
                    {/if}</label><br>
                <label><b>Message:</b> {$domainDetail->message}</label>
            </div>

            <div class="col-md-12" style="text-align: right;">
                {* <button class="btn btn-primary">Total Mailbox:  {$getMailBox['result']->data->results|count}/{$countMailBox} </button> *}
                <button id='mailbox_btn' class="tab-mailbox">Manage Mailboxes  ({if $getMailBox['result']->data->results}{$getMailBox['result']->data->results|count}{else}0{/if}/{$countMailBox}) </button>
                <button id='mailboxAliases_btn' class="tab-mailbox">Manage Mailbox Aliases ({$getAlias|count})</button>
            </div>

            <div class="col-md-12 cus-container" style="display: none;" id='mailbox_container'>
                <div class='mailbox-header'>
                    <h5>Manage Mailboxes</h5>
                        <button class="btn btn-primary" id='createMail'>Add email account</button>
                </div>
                <table id='mailbox' class="datatable">
                    <thead>
                        <tr>
                            <th>Email </th>
                            <th>Subscription</th>
                            <th>Status</th>
                            <th>SMTP/IMAP</th>
                            <th>Order Date</th>
                            <th>Renewal date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>

                        {foreach from=$getMailBox['result']->data->results item=item key=key name=name}
                            <tr>
                                <td>{$item->mailbox}@{$item->domain->name}.{$item->domain->extension}</td>
                                <td>{if $item->subscription_period ==1} Monthly{else}Yearly{/if}</td>
                                <td><span class='cus_status {$item->mailbox_status}'>{$item->mailbox_status}</span></td>
                                <td>{$configoption5}/{$configoption6}</td>
                                <td>{$item->created_at}</td>
                                <td>{$item->renew_at}</td>
                                <td>
                                    <a title="Username & Change Password" changeValue='{$item->id}' class='changePassword'><i
                                            class="fas fa-key"></i></a>
                                    <a title="Add Alias" changeValue='{$item->mailbox}'
                                        changeValue2='{$item->domain->name}.{$item->domain->extension}' class='addAlias'><i
                                            class="fas fa-at"></i></a>
                                    <a title="View DNS File" class="getDNS"><i class="far fa-file-alt"></i></a>
                                    <a title="Delete Email" class='selectDeleteMailBox' changeValue='{$item->id}'><i
                                            class="far fa-trash-alt"></i></a>
                                    <a title="View Alias" class='viewAlias'
                                        onclick="viewAlias('{$item->mailbox}@{$item->domain->name}.{$item->domain->extension}')"><i
                                            class="fas fa-envelope"></i></a>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>

            <div class="col-md-12  cus-container" style="display: none;" id='mailboxAliases_container'>
                <div class='mailbox-header'>
                    <h5>Manage Mailbox Aliases</h5>
                </div>
                <table id='mailboxAliases' class="datatable">
                    <thead>
                        <tr>
                            <th>Alias</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$getAlias item=item key=key }
                            <tr>
                                <td>{$item->alias}</td>
                                <td>{$item->mailbox}</td>
                                <td>{if $item->active}<span class="cus_status active">Active</span>{else}Inactive{/if}</td>
                                <td>
                                    {* <a value='{$item->id}' class="btn btn-primary editAlias"><i class="fas fa-edit"></i></a>  *}
                                    <a value='{$item->id}' class="btn btn-danger deleteAlias" deleteAlias='{$item->alias}'><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>

        </div>



        {* start modal  *}
        <div class="modal fade" id="createNewMailCowCenter" tabindex="-1" role="dialog"
            aria-labelledby="createNewMailCowCenterTitle" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createNewMailCowLongTitle">Add new email account</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="post" id='creteNewMailForm'>
                        <div class="modal-body">
                            <div class='form-div-section'>
                                <label>Full name</label>
                                <input type="hidden" name="formAction" value="addMailbox">
                                <input type="text" name="full_name" id='full_name' class="form-control">
                                <label class='error-log'>Enter The Full Name</label>
                            </div>
                            <div class='row '>
                                <div class="col-md-8">
                                    <label>Mailbox</label>
                                    <input type="text" name="mailbox" id="mailbox_field" class="form-control">
                                    <label class='error-log'>Enter The Mailbox</label>
                                </div>
                                <div class="col-md-4">
                                    <label>Domain name</label>
                                    <input type="text" class="form-control" style="cursor: not-allowed;" value="@{$domainDetail->domain->name}.{$domainDetail->domain->extension}" disabled>
                                    {* <label class="form-control">@{$domainDetail->domain->name}.{$domainDetail->domain->extension}</label> *}
                                </div>
                            </div>
                            {* <div class='form-div-section'>
                                <label>Subscription period</label>
                                <select name="Subscriptionperiod" id="Subscriptionperiod" class="form-control">
                                    <option value="1">Monthly</option>
                                    <option value="12">Yearly</option>
                                </select>
                            </div> *}
                            <div class='form-div-section'>
                                <label>Password</label>
                                <div class="input-group" bis_skin_checked="1">
                                    <input type="password" name="password" id="password" class="form-control">
                                    <span class="form-icon-i" style="cursor: pointer;" id="password_eye"><i class="fa fa-eye-slash"  ></i></span>
                                </div>
                                <label class='error-log'>Password should be minumum 8 characters.</label>
                            </div>
                            <div class='form-div-section'>
                                <input type="checkbox" name="resetPasswordFirstLogin" id="resetPasswordFirstLogin" style="cursor: pointer;"><label style="cursor: pointer;" for="resetPasswordFirstLogin"> &nbsp;&nbsp; Reset password on
                                    first login</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" id='submitBtnNewEmail'>Add email account</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="deleteAlias" tabindex="-1" role="dialog" aria-labelledby="deleteAliasLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteAliasLabel">Delete Alias <span id='deleteAliasHeading'></span></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="post" action="">
                        <div class="modal-body">
                            <input type="hidden" name='formAction' value='DeleteAlias'>
                            <input type="hidden" name='aliasId' id='aliasId'>
                            Are you sure you want to delete the selected alias ?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-danger">Delete Alias</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <div class="modal fade" id="addAlias" tabindex="-1" role="dialog" aria-labelledby="addAliasLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addAliasLabel">Add new alias for email</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="post" action="">
                        <div class="modal-body">
                            <input type="hidden" name='formAction' value='addAlias'>
                            <input type="hidden" name='emailMailBox' id='emailMailBox'>
                            <div class='row'>
                                <div class='col-md-6'><label>Alias</label><input type="text" placeholder="Enter The Alias"
                                        class="form-control" name='alias' required></div>
                                <div class='col-md-6'><label>Domain</label><input type="text" class="form-control" style="cursor: not-allowed;"
                                        id="emailMailBoxs" disabled></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Add Alias</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="changePassMailCowCenter" tabindex="-1" role="dialog"
            aria-labelledby="changePassMailCowCenterTitle" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createEditMailCowLongTitle">Edit Username And Change Password</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="post" id='changePassForm'>
                        <input type="hidden" name="formAction" value="changePassword">
                        <input type="hidden" name="mailbox" id='editMailId' class="form-control">
                        <div class="modal-body">
                            <div class='form-div-section'>
                                <label>Username</label>
                                <input type="text" name="user_name" id='user_name' class="form-control">
                                <span id="user_name_err"></span>
                                <label class='error-log'>Enter The Username</label>
                            </div>
                            <div class='form-div-section'>
                                <label>New Password</label>
                                <div class="input-group">
                                    <input type="password" name="new_password" id='new_password' class="form-control">
                                    <span class="form-icon-i"><i class="fa fa-eye-slash" id="new_pass_eye"></i></span>
                                </div>
                                <span id="new_password_err"></span>
                                <label class='error-log'>Enter The New Password</label>
                            </div>
                            <div class='form-div-section'>
                                <label>Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" id='confirm_password' class="form-control">
                                    <span class="form-icon-i"><i class="fa fa-eye-slash"  id="confirm_pass_eye"></i></span>
                                </div>
                                <span id="confirm_password_err"></span>
                                <label class='error-log'>Confirm The New Password</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" id='submitBtnChangePass'>Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="deleteMailBox" tabindex="-1" role="dialog" aria-labelledby="deleteMailBoxLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteMailBoxLabel">Delete Mailbox</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="post" action="">
                        <div class="modal-body">
                            <input type="hidden" name='formAction' value='DeleteMailbox'>
                            <input type="hidden" name='deleteMailbox' id='deleteMailboxID'>
                            
                            Are you sure you want to delete the selected selected Mailbox ?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-danger">Delete Mailbox</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade bd-example-modal-lg" id="DNSBox" tabindex="-1" role="dialog" aria-labelledby="DNSBoxLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="DNSBoxLabel">DNS records for email</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id='DNSBoxBody'>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="waitingBoxModal" tabindex="-1" role="dialog" aria-labelledby="waitingBoxModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="waitingBoxModalLabel"></h5>
                    </div>
                    <div class="modal-body" style="text-align: center;">
                        <p id='waitingMessage'></p>
                        <span>Please Wait...</span>
                        <br>
                        <span style="font-size: 20px;"><i class="fas fa-spinner fa-spin"></i></span>
                    </div>
                </div>
            </div>
        </div>


        {* end modal  *}
       <script>
            dnsData='';
       </script>
        {foreach from=$customDNSRecord item=item}
            {assign var="parts" value="|"|explode:$item}
            <script>
                dnsData += "<tr><td><textarea  class='form-control' >{$parts[0]}</textarea></td><td class='record_type'><span>{$parts[1]}</span></td><td>{$parts[3]}</td><td class='copy_data_td'><textarea class='form-control record_value' style='' readonly>{$parts[2]}</textarea><a class='' onclick='copyData($(this),`{$parts[2]}`)' ><i class='far fa-copy'></i></a></td></tr>";
            </script>
        {/foreach}

        <script>
            $(document).ready(function() {

                new DataTable('#mailbox', {});
                mailboxAliases = new DataTable('#mailboxAliases', {});


                $('#createMail').click(function() {
                    $('#createNewMailCowCenter').modal('show');
                    $('.error-log').hide();
                });

                $('#mailbox_btn').click(function() {
                    $('.tab-mailbox').removeClass('active');
                    $(this).addClass('active');
                    $('.cus-container').hide();
                    $('#mailbox_container').show();
                });

                $('#mailbox_btn').click();

                $('#mailboxAliases_btn').click(function() {
                    $('.tab-mailbox').removeClass('active');
                    $(this).addClass('active');
                    $('.cus-container').hide();
                    $('#mailboxAliases_container').show();
                    mailboxAliases.search('').draw();
                });

                $(document).on('click', '.editAlias', function() {

                });

                $(document).on('click', '.deleteAlias', function() {
                    id = $(this).attr('value');
                    $('#deleteAliasHeading').text('('+$(this).attr('deleteAlias')+')');
                    $('#deleteAlias').modal('show');
                    $('#aliasId').val(id);
                });

                $(document).on('click', '.selectDeleteMailBox', function() {
                    id = $(this).attr('changeValue');
                    $('#deleteMailBox').modal('show');
                    $('#deleteMailboxID').val(id);
                });

                $('#password_eye').click(function(){
                    if($('#password').attr('type')=='password'){
                        $('#password_eye').html('<i class="fa fa-eye"  ></i>');
                        $('#password').attr('type','text')
                    }else{
                        $('#password_eye').html('<i class="fa fa-eye-slash"  ></i>');
                        $('#password').attr('type','password')
                    }
                });

                $('#submitBtnNewEmail').click(function(event) {
                    event.preventDefault();
                    $('.error-log').hide(); // Hide all error messages initially

                    let error = 0; // Use 'let' for variable declaration

                    $('#creteNewMailForm input').each(function(index) {
                        var id = $(this).attr('id');
                        var value = $(this).val();

                        // Check required fields
                        if (id === 'full_name' || id === 'mailbox_field' || id === 'password') {
                            if (value.length === 0 && (id === 'full_name' || id ===
                                'mailbox_field')) {
                                console.log(value);
                                $('#' + id).parent().find('.error-log').show();
                                error++; // Increment error count
                            }

                            if (value.length < 8 && (id === 'password')) {
                                $('#' + id).parent().find('.error-log').show();
                                error++; // Increment error count
                            }
                        }
                    });

                    if (error == 0) {
                        $('#creteNewMailForm').submit();
                    }
                });


                // $('#submitBtnEditEmail').click(function(event) {
                //     event.preventDefault();
                //     $('.error-log').hide(); // Hide all error messages initially

                //     let error = 0; // Use 'let' for variable declaration

                //     $('#editMailBoxForm input').each(function(index) {
                //         var id = $(this).attr('id');
                //         var value = $(this).val();

                //         // Check required fields
                //         if (id === 'edit_full_name' || id === 'edit_password' ||  id === 'edit_confirm_password' ) {
                //             if (value.length === 0 && (id === 'edit_full_name')) {
                //                 $('#' + id).parent().find('.error-log').show();
                //                 error++; // Increment error count
                //             }

                //             if (value.length <= 5 && (id === 'edit_password')) {
                //                 $('#' + id).parent().find('.error-log').show();
                //                 error++; // Increment error count
                //             }
                //         }
                //     });

                //     if (error == 0) {
                //         $('#editMailBoxForm').submit();
                //     }
                // });

            });

            // Change Password on Click
            $(document).on('click', '.changePassword', function() {
                emailId = $(this).attr('changeValue');

                // $('#editMailId').val(emailId);
                // $('#changePassMailCowCenter').modal('show');


                $('#waitingBoxModal').modal('show');
                $('#waitingBoxModalLabel').text('Edit Username And Change Password');
                emailId = $(this).attr('changeValue');
                ajaxData = {
                    emailId: emailId,
                    formAction: "getEmailDetail"
                }
                $.ajax({
                    type: "POST",
                    url: "",
                    data: ajaxData,
                    cache: false,
                    dataType: "json",
                    success: function(data) {
                        console.log(data.mailbox.result);
                        $('#waitingBoxModal').modal('hide');
                        $('#changePassMailCowCenter').modal('show');
                        $('#user_name').val(data.mailbox.result.data.name);
                        $('#editMailId').val(data.mailbox.result.data.mailbox);
                    }
                });
            });

            $(document).on('click', '.addAlias', function() {
                $('#waitingBoxModalLabel').text('Add New Alias');
                emailId = $(this).attr('changeValue');
                domain = $(this).attr('changeValue2');
                $('#addAlias').modal('show');

                $('#emailMailBox').val(emailId);
                $('#emailMailBoxs').val('@' + domain);
            });

            $(document).on('click', '.getDNS', function() {
                $('#waitingBoxModal').modal('show');
                $('#waitingBoxModalLabel').text('DNS records for email');
                ajaxData = {
                    formAction: "getDNS"
                }
                $.ajax({
                    type: "POST",
                    url: "",
                    data: ajaxData,
                    cache: false,
                    dataType: "json",
                    success: function(data) {
                        $('#waitingBoxModal').modal('hide');

                        htmlTable = `<p>Below you can find a list of recommended DNS records. 
                                        While some are mandatory for a mail server (MX), 
                                        others are recommended to build a good reputation score 
                                        (TXT/SPF) or used for auto-configuration of mail clients.
                                    </p>
                            <table style='width:100%; background-color:#f3f3f3;'>
                            <tr>
                                <th style='text-align:center;'>Name/Host</th>
                                <th style='text-align:center;'>Type</th>
                                <th style='text-align:center;'>Priority</th>
                                <th style='text-align:center;'>Value</th>
                            </tr>
                        `;
                        htmlTable +=dnsData;

                        if(data.result.data.dkim_text.length>0){
                            htmlTable += "<tr>";
                            htmlTable +=
                                "<td style=''><textarea class='form-control record_name' style='' readonly> dkim_text </textarea></td>";
                            htmlTable += "<td style='' class='record_type'><span>TXT</span></td><td></td>";
                            htmlTable +=
                                "<td style='' class='copy_data_td'><textarea class='form-control record_value' style='' readonly>" +
                                data.result.data.dkim_text + "</textarea>";
                            htmlTable +=
                                '<a class="btn btn-info" onclick=copyData($(this),"' +
                               data.result.data.dkim_text + '")><i class="far fa-copy"></i></a></td>';
                            htmlTable += "</tr>";
                        }

                        data.result.data.dkim_record.forEach(record => {
                            htmlTable += "<tr>";
                            htmlTable +=
                                "<td style=''><textarea class='form-control record_name' style='' readonly>" +
                                record.name + "</textarea></td>";
                            htmlTable += "<td style='' class='record_type'><span>" + record.record_type + "</span></td><td></td>";
                            htmlTable +=
                                "<td style='' class='copy_data_td'><textarea class='form-control record_value' style='' readonly>" +
                                record.value + "</textarea>";
                            htmlTable +=
                                '<a class="btn btn-info" onclick=copyData($(this),"' +
                                record.value + '")><i class="far fa-copy"></i></a></td>';
                            htmlTable += "</tr>";
                        });
                        htmlTable += "</table>";

                        $('#DNSBox').modal('show');
                        $('#DNSBoxBody').html(htmlTable);
                    }
                });
            });

            // Change Password Submit

            $(document).on('click', '#submitBtnChangePass', function(e) {
                e.preventDefault();
                var error = false;
                var user_name = $('#user_name').val();
                var newPass = $('#new_password').val();
                var confirmPass = $('#confirm_password').val();

                if (user_name == '') {
                    $('#user_name_err').html("Please enter username.").css({
                        'color': 'red',
                        'font-size': 'small',
                    });
                    error = true;
                }

                if (newPass != '') {

                    if (newPass.length < 8) {
                        $('#new_password_err').html("Password should be minumum 8 characters.").css({
                            'color': 'red',
                            'font-size': 'small',
                        });

                        error = true;
                    } else {
                        $('#new_password_err').html("");

                        if (confirmPass != '') {
                            if (newPass === confirmPass) {
                                $('#confirm_password_err').html("Password matched").css({
                                    'color': 'green',
                                    'font-size': 'small',
                                });
                            } else {
                                $('#confirm_password_err').html("Passwords do not match").css({
                                    'color': 'red',
                                    'font-size': 'small',
                                });

                                
                                error = true;
                            }
                        } else {
                            $('#confirm_password_err').html("Please enter confirm password.").css({
                                'color': 'red',
                                'font-size': 'small',
                            });
                            error = true;
                        }
                    }

                } else {
                    $('#new_password_err').html("Please enter password.").css({
                        'color': 'red',
                        'font-size': 'small',
                    });

                    $('#confirm_password_err').html("Please enter confirm password.").css({
                        'color': 'red',
                        'font-size': 'small',
                    });

                    error = true;
                }

                if (!error) {
                    $('#changePassForm').submit();
                }
            });

            // new password eye toggle
            $('#new_pass_eye').click(function() {
                let new_pass = $('#new_password');
                let icon = $(this);

                if (new_pass.attr('type') === 'password') {
                    new_pass.attr('type', 'text');
                } else {
                    new_pass.attr('type', 'password');
                }

                icon.toggleClass('fa-eye fa-eye-slash');
            });
            // confirm password eye toggle
            $('#confirm_pass_eye').click(function() {
                let confirm_pass = $('#confirm_password');
                let icon = $(this);

                if (confirm_pass.attr('type') === 'password') {
                    confirm_pass.attr('type', 'text');
                } else {
                    confirm_pass.attr('type', 'password');
                }

                icon.toggleClass('fa-eye fa-eye-slash');
            });


            function copyData(target, data) {
                navigator.clipboard.writeText(data);
                Swal.fire({
                    title: 'Successfully Copied Text',
                    icon: 'success',
                    timer: 1000, // Close after 1 second
                    showConfirmButton: false
                });
            }

            function viewAlias(data) {
                $('#mailboxAliases_btn').click();
                mailboxAliases.search(data).draw();

            }
        </script>
    {/if}

</div>

