<?php
  $fluid->loadHeader('Block Manager', '
    <link href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
  ');

  $bc = $fluid->components['Breadcrumbs'];
  $db = $fluid->components['DB'];

?>
<style type="text/css">
table{ font-size: 12px;}

tr.retired *{ color: gray; font-style: italic}
tr.inactive * {color: #777;}
a.btn-success{ padding: 2px; padding-left: 8px; padding-right: 8px;}
.btn-success span{ color: white; font-size: 11px;}
</style>


<?php
//app action confirmation message via session
if(isset($_SESSION['block'])){
?>
<div role="alert" class="alert alert-success alert-dismissible fade in" style="position: relative; top: 10px;">
  <button aria-label="Close" data-dismiss="alert" class="close" type="button">
    <span aria-hidden="true">&times;</span>
  </button>
  <strong><?php echo $_SESSION['block']; ?></strong>
</div>
<?php
unset($_SESSION['block']);
}

  ?>

<div class="container-fluid" role="main">

<?php

  $bc->set();
  $bc->display();
 ?>

<h3>Block Manager</h3>

<!--<div class="form-group">
  <label for="sel_content">Content Type</label>
  <select id="sel_content" class="form-control">
    <option value="page">Page</option>
    <option value="FAQ"></option>
  </select>
</div>-->



<br />
  <a href="/block/text/add" class="btn btn-primary add " data-toggle="tooltip" title="Add Block"><span class="glyphicon glyphicon-plus"></span> Add Block</a>
<br /><br />
<table class="display" id="contentTable">
  <thead>
  <tr>
    <!--<th>Block Type</th>-->
    <th>Title</th>
    <th>Notes</th>
    <th>Last Modified</th>
    <th>Modified By</th>
    <th>Action</th>
  </tr>
  </thead>
  <tbody>
<?php
    $prefix = "block_";
    $arrTables = $db->getPrefixTables($prefix);
    $countTables = count($arrTables);
    for ($i = 0; $i < $countTables; $i++){

        $result = $db->select("select id,title,notes,modified,modified_by from {$arrTables[$i]} where site_id=".$fluid->site->info->id);
        $count = count($result);
        for($j = 0; $j < $count; $j++){
          $type = str_replace($prefix,"",$arrTables[$i]);
          echo '<tr>';
          #echo '<td>'.ucFirst($type).'</td>';
          echo '<td>'.$result[$j]['title'].'</td><td>'.$result[$j]['notes'].'</td><td>'.$result[$j]['modified'].'</td>'.
               '<td>'.$result[$j]['modified_by'].'</td><td><a class="btn btn-success" href="/block/'.$type.'/edit/'.$result[$j]['id'].'"><span class="glyphicon glyphicon-pencil"></span></a></td></tr>';
        }
    }
    ?>
  </tbody>
</table>
</div>
<?php
	$fluid->loadFooter(
    '<script src="https://cdn.datatables.net/1.10.13/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript">
    $(document).ready(function(){
        $("#contentTable").DataTable();
    });
    $(".edit").on("click",function(){
      var idVal = $(this).attr("id").split("_")[1];
      location.href="/page/edit/"+idVal;
    });
    </script>'
  );
