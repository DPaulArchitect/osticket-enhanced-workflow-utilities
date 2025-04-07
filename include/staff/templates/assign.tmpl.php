<?php
global $cfg;

$form = $form ?: AssignmentForm::instantiate($info);

if (!$info[':title'])
    $info[':title'] = __('Assign');
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
<form class="mass-action" method="post"
    name="assign"
    id="<?php echo $form->getFormId(); ?>"
    action="<?php echo $action; ?>">
    <table id="'assignForm" width="100%">
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
             $options = array('template' => 'simple', 'form_id' => 'assign');
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
            echo $verb ?: __('Assign'); ?>">
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

        // Function to initialize the canned response select box within the assign form
        async function initializeCannedResponseAssign(formElement) {
            console.log("initializeCannedResponseAssign called with:", formElement);

            // Wait for the Redactor editor container (not the textarea directly)
            const redactorEditorContainer = await waitForElement('.redactor-box', formElement);
            console.log("redactorEditorContainer:", redactorEditorContainer);

            // Remove any existing select block within this form
            formElement.find('#cannedRespAssign').closest('div').remove();

            // Create and append the new select block
            const selectBlockHtml = `
                <div>
                    <?php
                    if ($errors['assign'])
                        echo sprintf('<div class="error">%s</div>', $errors['assign']);

                    if ($cfg->isCannedResponseEnabled()) { ?>
                    <label aligntop><strong>Canned Responses:</strong></label><br>
                    <select id="cannedRespAssign" name="cannedRespTransfer">
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
            $(selectBlockHtml).insertBefore(redactorEditorContainer.closest('div'));

            // Initialize select2 for the new select block
            formElement.find('select#cannedRespAssign').select2({width: '350px'});
            formElement.find('select#cannedRespAssign').on('select2:opening', function(e) {
                var redactor = $('.richtext', $(this).closest('form')).data('redactor');
                if (redactor)
                    redactor.api('selection.save');
            });

            formElement.find('select#cannedRespAssign').change(function() {
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
                        // Target Redactor using the editor container
                        // Pass a STRING selector to $R()
                        var redactor = $R('#' + formElement.find('textarea.richtext.no-bar.small.redactor-source').attr('id'));

                        console.log("TextArea element:", box);
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
        }

        // Initialize the canned response select box when the assign form is present
        async function initializeOnAssignLoad() {
            const assignContainer = await waitForElement('#<?php echo $form->getFormId(); ?>');
            console.log("initializeOnAssignLoad - assignContainer:", assignContainer);
            if (assignContainer.length) {
                const assignForm = assignContainer; // The container *is* the form in this case
                await initializeCannedResponseAssign(assignForm);
            } else {
                console.log("initializeOnAssignLoad - Assign form container not found");
            }
        }
        initializeOnAssignLoad();

        // Refresh the page upon form submission
        $(document).on('submit', '#<?php echo $form->getFormId(); ?>', function(e) {
            e.preventDefault();
            var form = $(this);
            console.log("Form submission intercepted:", form);
            $.ajax({
                url: form.attr('action'),
                type: form.attr('method'),
                data: form.serialize(),
                success: function(response) {
                    console.log("AJAX success (form submission):", response);
                    //location.reload();
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error (form submission):", error);
                    //location.reload();
                }
            });
        });

        /** Refresh the page when the close button within the assign form is clicked
        $(document).on('click', '#<?php echo $form->getFormId(); ?> .close', function(e) {
            e.preventDefault(); // Prevent the default link behavior
            console.log("Close button clicked");
            location.reload();
        });*/ 
    });
</script>
