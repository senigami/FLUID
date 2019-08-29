<?php
  $fluid->loadHeader('Page Content Manager', '
    <link href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
  ');

  $fluid->page->title = "Page Content Manager";
  $fluid->components['Breadcrumbs']->set();
  $fluid->components['Breadcrumbs']->updateTitleID($fluid->page->title,"");

  function getStatus($publish,$retire){
    $statusStr = "";
    if ($publish == 1)     $statusStr = '<span class="glyphicon glyphicon-log-in"></span> &nbsp;Active';
    else if ($retire == 1) $statusStr = '<span class="glyphicon glyphicon-log-out"></span> &nbsp;Retired';
    else                   $statusStr = "<span class='glyphicon glyphicon-unchecked'></span> &nbsp;Inactive";
    return $statusStr;
  }

  function getPath($parentLabel,$label,$url,$parentURL){
    if($parentLabel == null){
      if ($label == null) return "";
      else{
        return '<a href="'.$url.'" target="_blank">'.$label."</a>";
      }
    }
    else{
      return '<a href="'.$parentURL.'">'.$parentLabel.'</a> > <a href="'.$url.'">'.$label.'</a>';
    }
  }

  function getURL($alias,$id){
    if($alias == null || $alias == "") return "/page/".$id;
    else return "/".$alias;
  }


?>
<link href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
<style type="text/css">
table{ font-size: 12px;}

tr.retired *{ color: gray; font-style: italic}
tr.inactive * {color: #777;}
a.btn-success{ padding: 2px; padding-left: 8px; padding-right: 8px;}
.btn-success span{ color: white; font-size: 11px;}
</style>
<div class="container-fluid" role="main">
<?php



  
  $fluid->components['Breadcrumbs']->display();



  if( content('system_message') ): ?>
	<div role="alert" class="alert alert-success alert-dismissible fade in">
		<button aria-label="Close" data-dismiss="alert" class="close" type="button">
			<span aria-hidden="true">&times;</span>
		</button>
		<strong><?= content('system_message'); ?></strong>
	</div>
<?php endif; ?>

<h3>Page Content Manager</h3>

<!--<div class="form-group">
  <label for="sel_content">Content Type</label>
  <select id="sel_content" class="form-control">
    <option value="page">Page</option>
    <option value="FAQ"></option>
  </select>
</div>-->
<br />
  <a href="/page/add" class="btn btn-primary add " data-toggle="tooltip" title="Add Page"><span class="glyphicon glyphicon-plus"></span> Add Page</a>
<br /><br />
<table class="display" id="contentTable">
  <thead>
  <tr>
    <th>Title</th>
    <th>URL Alias</th>
    <th>Menu Path</th>
    <th>Status</th>
    <th>Last Modified</th>
    <th>Modified By</th>
    <th>Action</th>
  </tr>
  </thead>
  <tbody>
    <?php
    $db = $fluid->components['DB'];
    $result = $db->select("select lb.id, lb.site_id, lb.alias, lb.title, lb.publish,
                          lb.retire, lb.menu_id,
                          lb.modified_by, lb.created, lb.modified, m.parent, m.url,
                          m.label,m2.label as parent_label,m2.url as parent_url from list_basic lb
                          left join menu m on lb.menu_id = m.id
                          left join menu m2 on m.parent = m2.id
                          where lb.site_id = '{$fluid->site->info->id}'");

    for ($i = 0; $i < sizeof($result); $i++){
      if ($result[$i]['retire'] == 1)
				echo '<tr class="retired">';
      else
				if ($result[$i]['publish'] == 0)
					echo '<tr class="inactive">';
				else
					echo '<tr />';

      echo '<td class="title"><a href="'.getURL($result[$i]['alias'],$result[$i]['id']).'" target="_blank">' . $result[$i]['title'] . '</a></td>
				<td class="alias">' . $result[$i]['alias'] . '</td>
				<td class="menuPath">'.getPath($result[$i]['parent_label'],$result[$i]['label'],$result[$i]['url'],$result[$i]['parent_url']).'</td>
				<td class="status">';
					echo getStatus($result[$i]['publish'],$result[$i]['retire']);
					echo '</td>
				<td class="lastMod">'.$result[$i]['modified'].'</td>
				<td class="modBy"><a href="http://people.qualcomm.com/People?query='.$result[$i]['modified_by'].'" target="_blank">' . $result[$i]['modified_by'].'</td>
				<td class="action"><a class="btn btn-success" href="/page/edit/'.$result[$i]['id'].'"><span class="glyphicon glyphicon-pencil"></span></a></td>
			</tr>';
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
