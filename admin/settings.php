<?php
/**
 * @author Saurabh
 *
 * @version 1.0
 * This template showssettings
 */
?>
<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}
add_action('admin_menu', 'Klaviyo_Custom_menu'); 
function Klaviyo_Custom_menu(){
    add_menu_page( 'Klaviyo Custom Properties', 'Klaviyo Custom', 'manage_options', 'Klaviyo_Custom', 'menu_init' );
} 
function menu_init(){   
  global $wpdb;
  $table_name = $wpdb->prefix . 'klaviyoCustomTable';
  if (isset($_POST['newsubmit'])) {	 
    $klaviyo_key = $_POST['klaviyo_key'];
    $public_api = $_POST['public_api'];	
    $report_url = $_POST['report_url'];
    $usps_url = $_POST['usps_url'];
    $wpdb->query("INSERT INTO $table_name(klaviyo_key,public_api,usps_url,report_url) VALUES('$klaviyo_key','$public_api','$report_url','$usps_url')");
    echo "<script>location.replace('admin.php?page=Klaviyo_Custom');</script>";
  }
  if (isset($_POST['uptsubmit'])) {	 
    $id = 1;
    $klaviyo_key = $_POST['klaviyo_key'];
    $public_api = $_POST['public_api'];
    $report_url = $_POST['report_url'];
    $usps_url = $_POST['usps_url'];
    $wpdb->query("UPDATE $table_name SET klaviyo_key='$klaviyo_key',public_api='$public_api', usps_url='$usps_url', report_url='$report_url' WHERE id='$id'");
    echo "<script>location.replace('admin.php?page=Klaviyo_Custom');</script>";
  }
  if (isset($_GET['del'])) {
    $del_id = $_GET['del'];
    $wpdb->query("DELETE FROM $table_name WHERE id='$del_id'");
    echo "<script>location.replace('admin.php?page=Klaviyo_Custom');</script>";
  }
  ?>
  <div class="wrap">
    <h2>Klaviyo Custom properties Settings</h2>
	<?php $result = $wpdb->get_results("SELECT * FROM $table_name where id=1");	?>
	 <form action="" method="post">
		<table class="form-table">     
		  <tbody>   
				<tr>			
					<th scope="row"><label for="blogname">Public API Keys</label></th><td><input type="text" id="public_api" name="public_api" value="<?php if($result[0]->public_api){echo $result[0]->public_api;} ?>"></td></td>
				</tr>
				<tr>
					<th scope="row"><label for="blogname">Private API Keys</label></th><td><input type="text" id="klaviyo_key" name="klaviyo_key" value="<?php if($result[0]->klaviyo_key){echo $result[0]->klaviyo_key;} ?>"></td>          
					
				 </tr>

         <tr>
					<th scope="row"><label for="blogname">USPS URL Link</label></th><td><input type="text" id="usps_url" name="usps_url" value="<?php if($result[0]->usps_url){echo $result[0]->usps_url;} ?>"></td>          
					
				 </tr>
         <tr>
					<th scope="row"><label for="blogname">Report URL Link</label></th><td><input type="text" id="report_url" name="report_url" value="<?php if($result[0]->report_url){echo $result[0]->report_url;} ?>"></td>          
					
				 </tr>
				 
				 <tr>
					<td>
						<?php 
							if(count($result) >0){
								echo '<button class="button button-primary" id="uptsubmit" name="uptsubmit" type="submit">Update</button>';
								
							}else{
								echo '<button class="button button-primary" id="newsubmit" name="newsubmit" type="submit">Submit</button>';
							}
					 ?>
					</td>
				</tr>
				   
			</tbody>
		</table>
     </form>  
  </div>
  <?php
}