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
    <td>
                <?php
                if ($errors['Transfer'])
                    echo sprintf('<div class="error">%s</div>',
                            $errors['Transfer']);

                if ($cfg->isCannedResponseEnabled()) { ?>
                  <div>
                    <label aligntop><strong>Canned Responses:</strong></label>
                    <select id="cannedRespTransfer" label="Canned Response" name="cannedRespTransfer">
                        <option value="0" selected="selected"><?php echo __('Select a canned response');?></option>
                        <option value='original'><?php echo __('Original Message'); ?></option>
                        <option value='lastmessage'><?php echo __('Last Message'); ?></option>
                        <?php
                        if(($cannedResponses=Canned::responsesByDeptId($ticket->getDeptId(), null, [2] ))) {
                            echo '<option value="0" disabled="disabled">
                                ------------- '.__('Premade Replies').' ------------- </option>';
                            foreach($cannedResponses as $id =>$title)
                                echo sprintf('<option value="%d">%s</option>',$id,$title);
                        }
                        ?>
                    </select>
                    </div>
                    </td></tr>
                    <tr><td colspan="2">
                <?php } # endif (canned-resonse-enabled)
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
    $('form select#cannedRespTransfer').select2({width: '350px'});
    $('form select#cannedRespTransfer').on('select2:opening', function (e) {
        var redactor = $('.richtext', $(this).closest('form')).data('redactor');
        if (redactor)
            redactor.api('selection.save');
    });

    $('form select#cannedRespTransfer').change(function() {

        var fObj = $(this).closest('form');
        var cid = $(this).val();
        var tid = $(':input[name=id]',fObj).val();
        $(this).find('option:first').attr('selected', 'selected').parent('select');

        var $url = 'ajax.php/kb/canned-response/'+cid+'.json';
        if (tid)
            $url =  'ajax.php/tickets/'+tid+'/canned-resp/'+cid+'.json';

        $.ajax({
                type: "GET",
                url: $url,
                dataType: 'json',
                cache: false,
                success: function(canned){
                    //Canned response.
                    var box = $('#_transferText', fObj),
                        redactor = $R('#_transferText.richtext');
                    if (canned.response) {
                        if (redactor) {
                            redactor.api('selection.restore');
                            redactor.insertion.insertHtml(canned.response);
                        } else
                            box.val(box.val() + canned.response);
                    }
                    //Canned attachments.
                    var ca = $('.attachments', fObj);
                    if(canned.files && ca.length) {
                        var fdb = ca.find('.dropzone').data('dropbox');
                        $.each(canned.files,function(i, j) {
                          fdb.addNode(j);
                        });
                    }
                }
            })
            .done(function() { })
            .fail(function() { });
           
    });

</script>
