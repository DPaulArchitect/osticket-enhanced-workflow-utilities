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
        async function waitForElement(selector, context = document, timeout = 5000) {
            return new Promise((resolve, reject) => {
                let timer;
                const interval = setInterval(() => {
                    const element = $(selector, context);
                    console.log("waitForElement checking for:", selector, "found:", element.length > 0);
                    if (element.length) {
                        clearInterval(interval);
                        clearTimeout(timer);
                        resolve(element);
                    }
                }, 100);

                timer = setTimeout(() => {
                    clearInterval(interval);
                    reject(new Error(`waitForElement timed out waiting for ${selector}`));
                }, timeout);
            });
        }

        // Function to initialize the canned response select box within the transfer form
        async function initializeCannedResponseTransfer(formElement) {
            console.log("initializeCannedResponseTransfer called with:", formElement);

            // Wait for the Redactor editor container (not the textarea directly)
            try {
                const redactorEditorContainer = await waitForElement('.redactor-box', formElement);
                console.log("redactorEditorContainer:", redactorEditorContainer);

                // Remove any existing select block within this form
                formElement.find('#cannedRespTransfer').closest('div').remove();

                // Create and append the new select block
                const selectBlockHtml = `
                    <div>
                        <?php
                        if ($errors['Transfer'])
                            echo sprintf('<div class="error">%s</div>',
                                            $errors['Transfer']);

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
                                                if ($dept && $dept->canAppendSignature())
                                                $signature = $thisstaff->getSignature();
                                            break;
                                        } ?>
                        </select>
                    </div>
                `;
                $(selectBlockHtml).insertBefore(redactorEditorContainer.closest('div'));

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
                            console.log("AJAX success:", canned);
                            // Target the textarea using its classes
                            var box = formElement.find('textarea.richtext.no-bar.small.redactor-source');
                            console.log("TextArea element:", box);

                            // Target Redactor using the editor container
                            // Pass a STRING selector to $R()
                            var redactor = $R('#' + formElement.find('textarea.richtext.no-bar.small.redactor-source').attr('id'));

                            console.log("Redactor instance:", redactor);

                            if (canned.response) {
                                if (redactor) {
                                    redactor.insertion.insertHtml(canned.response);
                                } else {
                                    box.val(canned.response);
                                }
                            }
                            var ca = formElement.find('.attachments', fObj);
                            if (canned.files && ca.length) {
                                var fdb = ca.find('.dropzone').data('dropbox');
                                $.each(canned.files, function(i, j) {
                                    fdb.addNode(j);
                                });
                            }
                        }
                    }).done(function() {}).fail(function() {});
                });
            } catch (error) {
                console.error("initializeCannedResponseTransfer error:", error);
            }
        }

        // Initialize the canned response select box when the transfer form is present
        async function initializeOnTransferLoad() {
            console.log("initializeOnTransferLoad started");
            try {
                const transferForm = await waitForElement('#transfer');
                console.log("initializeOnTransferLoad - transferForm found:", transferForm);
                if (transferForm.length) {
                    // Explicitly call initializeCannedResponseTransfer after waitForElement
                    await initializeCannedResponseTransfer(transferForm);
                } else {
                    console.log("initializeOnTransferLoad - transferForm.length is 0");
                }
            } catch (error) {
                console.error("initializeOnTransferLoad error:", error);
            }
        }
        initializeOnTransferLoad();

        // Refresh the page upon form submission
        $(document).on('submit', '#transfer', function(e) {
            e.preventDefault();
            var form = $(this);
            console.log("Form submission intercepted:", form);
            $.ajax({
                url: form.attr('action'),
                type: form.attr('method'),
                data: form.serialize(),
                success: function(response) {
                    console.log("AJAX success (form submission):", response);
                    
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error (form submission):", error);
                    
                }
            });
        });
    });
</script>