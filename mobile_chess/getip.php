<?
function get_ip_address() {
if(getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")){
$ip = getenv("HTTP_CLIENT_IP");
}
elseif(getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")){
$ip = getenv("HTTP_X_FORWARDED_FOR");
}
elseif(getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")){
$ip = getenv("REMOTE_ADDR");
}
elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")){
$ip = $_SERVER['REMOTE_ADDR'];
}
else {
$ip = "Unknown";
}
return $ip;
}
echo get_ip_address();
?>
