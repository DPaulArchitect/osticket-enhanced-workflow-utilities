<?php
global $cfg;

$form = $form ?: TransferForm::instantiate($info);
?>
<h3 class="drag-handle"><?php echo $info[':title']; ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<div class="clear"></div>
<hr/>
<?php
if ($info['error']) {
    echo sprintf('<p id="msg_error">%s</p>', $info['error']);
} elseif ($info['warn']) {
    echo sprintf('<p id="msg_warning">%s</p>', $info['warn']);
} elseif ($info['msg']) {
    echo sprintf('<p id="msg_notice">%s</p>', $info['msg']);
} elseif ($info['notice']) {
   echo sprintf('<p id="msg_info"><i class="icon-info-sign"></i> %s</p>',
           $info['notice']);
}

$action = $info[':action'] ?: ('#');
?>
<div style="display:block; margin:5px;">
<form method="post" name="transfer" id="transfer"
    class="mass-action"
    action="<?php echo $action; ?>">
    <table width="100%">
        <?php
        if ($info[':extra']) {
            ?>
        <tbody>
            <tr><td colspan="2"><strong><?php echo $info[':extra'];
            ?></strong></td> </tr>
        </tbody>
        <?php
        }
       ?>
        <tbody>
            <tr><td colspan=2>
             <?php
             $options = array('template' => 'simple', 'form_id' => 'transfer');
             $form->render($options);
             ?>
            </td> </tr>
        </tbody>
    </table>
    
    <hr>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="reset" value="<?php echo __('Reset'); ?>">
            <input type="button" name="cancel" class="close"
            value="<?php echo __('Cancel'); ?>">
        </span>
        <span class="buttons pull-right">
            <input type="submit" value="<?php
            echo $verb ?: __('Transfer'); ?>">
        </span>
     </p>
</form>
</div>
<div class="clear"></div>

<script>
    $(document).ready(function() {
        async function waitForElement(selector, context = document) {
            return new Promise((resolve) => {
                const interval = setInterval(() => {
                    const element = $(selector, context);
                    if (element.length) {
                        clearInterval(interval);
                        resolve(element);
                    }
                }, 100);
            });
        }

        // Function to initialize the canned response select box within the transfer form
        async function initializeCannedResponse(formElement) {
            const redactorEditor = await waitForElement('.redactor-box', formElement);

            // Remove any existing select block within this form
            formElement.find('#cannedRespTransfer').closest('div').remove();

            // Create and append the new select block
            const selectBlockHtml = `
                <div>
                    <?php
                    if ($errors['Transfer'])
                        echo sprintf('<div class="error">%s</div>', $errors['Transfer']);

                    if ($cfg->isCannedResponseEnabled()) { ?>
                    <label aligntop><strong>Canned Responses:</strong></label><br>
                    <select id="cannedRespTransfer" name="cannedRespTransfer">
                        <option value="0" selected="selected">Select a canned response</option>
                        <option value="original">Original Message</option>
                        <option value="lastmessage">Last Message</option>
                        <?php
                        if(($cannedResponses=Canned::responsesByDeptId($ticket->getDeptId(), null, [2] ))) {
                            echo '<option value="0" disabled="disabled">
                                ------------- '.__('Premade Replies').' ------------- </option>';
                            foreach($cannedResponses as $id =>$title)
                                echo sprintf('<option value="%d">%s</option>',$id,$title);
                        }
                                ?>  <?php } # endif (canned-resonse-enabled)
                                        $signature = '';
                                        switch ($thisstaff->getDefaultSignatureType()) {
                                        case 'dept':
                                            if ($dept && $dept->canAppendSignature())
                                            $signature = $dept->getSignature();
                                        break;
                                        case 'mine':
                                            $signature = $thisstaff->getSignature();
                                        break;
                                    } ?>
                    </select>
                </div>
            `;
            $(selectBlockHtml).insertBefore(redactorEditor.closest('div'));

            // Initialize select2 for the new select block
            formElement.find('select#cannedRespTransfer').select2({width: '350px'});
            formElement.find('select#cannedRespTransfer').on('select2:opening', function(e) {
                var redactor = $('.richtext', $(this).closest('form')).data('redactor');
                if (redactor)
                    redactor.api('selection.save');
            });

            formElement.find('select#cannedRespTransfer').change(function() {
                var fObj = $(this).closest('form');
                var cid = $(this).val();
                var tid = $(':input[name=id]', fObj).val();
                $(this).find('option:first').attr('selected', 'selected').parent('select');

                var $url = 'ajax.php/kb/canned-response/' + cid + '.json';
                if (tid)
                    $url = 'ajax.php/tickets/' + tid + '/canned-resp/' + cid + '.json';

                $.ajax({
                    type: "GET",
                    url: $url,
                    dataType: 'json',
                    cache: false,
                    success: function(canned) {
                        var box = formElement.find('#_comments', fObj), // Scope of form
                            redactor = $R('#_comments.richtext', fObj); // Scope of form
                        if (canned.response) {
                            if (redactor) {
                                redactor.api('selection.restore');
                                redactor.insertion.insertHtml(canned.response);
                            } else {
                                box.val(box.val() + canned.response);
                            }
                        }
                        var ca = formElement.find('.attachments', fObj); // Scope of form
                        if (canned.files && ca.length) {
                            var fdb = ca.find('.dropzone').data('dropbox'); // Scope of form
                            $.each(canned.files, function(i, j) {
                                fdb.addNode(j);
                            });
                        }
                    }
                }).done(function() {}).fail(function() {});
            });
        }

        // Initialize the canned response select box when the transfer form is present within the #popup
        async function initializeOnPopupLoad() {
            const popupElement = await waitForElement('#popup');
            if (popupElement.length) {
                // Target the form using the 'is' attribute
                const transferForm = popupElement.find('form[id="transfer"]');
                if (transferForm.length) {
                    await initializeCannedResponse(transferForm);
                } else {
                    console.log("Transfer form (with id='transfer') not found within #popup");
                }
            } else {
                console.log("#popup element not found");
            }
        }
        initializeOnPopupLoad();

        // Refresh the page upon form submission within the #popup
        $(document).on('submit', '#popup form[id="transfer"]', function(e) {
            e.preventDefault();
            var form = $(this);
            $.ajax({
                url: form.attr('action'),
                type: form.attr('method'),
                data: form.serialize(),
                success: function(response) {
                    location.reload();
                },
                error: function(xhr, status, error) {
                    console.error("Form submission error:", error);
                    location.reload();
                }
            });
        });

        // Refresh the page when the close button within the #popup is clicked
        $(document).on('click', '#popup .close', function(e) {
            e.preventDefault(); // Prevent the default link behavior
            location.reload();
        });
    });
</script>