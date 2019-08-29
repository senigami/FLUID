<?php
include("get_fields.php");
?>
<br /><br />
<table id="table_fields" class="display" cellspacing="0" width="100%">
	<thead>
    	<tr>
				<th style="width: 100px">Edit</th>
				<?php
					for ($i = 0; $i < sizeof($cols); $i++){
							echo '<th>'.ucfirst($cols[$i]->Field).'</th>';
					}
				?>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>

<script type="text/javascript">
  var fields   = <?php echo $fields; ?>;
  var jsonCols = <?php echo $jsonCols; ?>;
	fieldsObj.init(fields, jsonCols);
</script>
