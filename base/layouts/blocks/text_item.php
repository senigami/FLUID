<?php
/************************REQUIRED VARIABLES*********************/
$access    = $fluid->components['Access'];
$blockText = $fluid->page->vars['block'];
$arrOverrides = $fluid->page->vars['arrOverrides'];
/***************************************************************/
?>

<div class="block_text block_text_item_<?=$blockText['id']?>" >
  <?php if($access->hasPermission("Basic Page","Edit") || in_array("Edit",$arrOverrides)){ ?>
    <div class="edit_area" >
  <?php } ?>

  <?php if($access->hasPermission("Basic Page","Edit") || in_array("Edit",$arrOverrides)){ ?>

     <div  style="text-align: right; margin-bottom: 10px">
      <a href="/block/text/edit/<?=$blockText['id']?>" class="btn btn-success edit pageBtn" title="Edit <?=$blockText['title']?>"><span class="glyphicon glyphicon-pencil"></span></a>
    </div>
  <?php }
  ?>

  <div>
    <?php if($blockText['show_title'] == 1){ ?>
    <h4><?= $blockText['title']  ?></h4>
    <?php } ?>
    <div><?=$blockText['content']?></div>
  </div>
  <?php if($access->hasPermission("Basic Page","Edit") || in_array("Edit",$arrOverrides)){ ?>
    </div>
  <?php } ?>
</div>
