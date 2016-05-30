<?php
function verifyemail($email){
	$pattern="^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$";
	
	return eregi($pattern, $email);
}

function verifyusername($username){
	$pattern="^[a-zA-Z0-9]{2,20}$";
	
	return eregi($pattern, $username);
}

function verifypassword($password){
	$pattern="^[a-zA-Z0-9]+$";
	
	return eregi($pattern, $password);
}

function isempty($str){
	return requiremin($str, 1);
}

function requiremin($str, $min){
	return strlen($str) >= $m;
}

function verifynum($phone){
	$pattern="^[0-9]+$";
	
	return eregi($pattern, $phone);
}

function goback($msg){
	jsalert($msg);
	jsback();
	exit();
}

function jsalert($msg){
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script language="javascript">
alert('<?php echo $msg; ?>');
//history.go(-1);
</script>
<?php
}

function jslocation($url){
?>
<script language="javascript">
location.href='<?php echo $url; ?>';
</script>
<?php }

function jsback(){
?>
<script language="javascript">
history.go(-1);
</script>
<?php } ?>