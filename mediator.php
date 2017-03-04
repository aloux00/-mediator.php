<?php 
     
    error_reporting(E_ALL); 

     
    // first we'll bind socket 
    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP); 
    if (!socket_bind($sock, "0.0.0.0", 8000)) 
        die("Could not bind socket\n"); 

    $ip = ''; 
    $port = 0; 

    socket_set_nonblock($sock); 

    $users = array(); 
    $ips = array(); 
    $ports = array(); 
    $dates = array(); 
    $data = array(); 

    do 
    { 
        $suxref[] =& $sock; 
        socket_select($suxref, $write = NULL, $except = NULL, 5); 
        $z = @socket_recvfrom($sock, $buf, 9999, 0, $ip, $port); 
        if ($z) 
        { 
            $ar = split("\r\n", $buf); 
            if (count($ar) == 3) 
            { 
                echo $ar[0] . " --> ". $ar[1] . "\r\n"; 

                // locate this user 
                $i = finduser($ar[0]); 
                if ($i>=0) 
                { 
                    // user found, update it 
                    $ips[$i] = $ip; 
                    $ports[$i] = $port; 
                    $dates[$i] = time(); 
                    $data[$i] = $ar[2]; 
                } 
                else 
                { 
                    // now found, add it 
                    array_push($users, $ar[0]); 
                    array_push($ips, $ip); 
                    array_push($data, $ar[2]); 
                    array_push($ports, $port); 
                    array_push($dates, time()); 
                    echo "Elements in array " . count($users) . "\r\n"; 
                } 

                // now check if remote peer is here too 
                $i = finduser($ar[1]); // ovdje ide $ar[1] 
                if ($i>=0) 
                { 
                    $out = "\000\000". $ips[$i] . "\r\n" . $ports[$i] . "\r\n" . $data[$i]; 
                    $len = strlen($out);                 
                    echo "Sending to $ip $port, total=" . socket_sendto($sock, $out, $len, 0, $ip, $port) . "\r\n"; 
                } 
            } 
        } 

        for ($i=0;$i<count($users);$i++) 
        { 
            if (time() - $dates[$i] > 15) 
            { 
                echo "Removing " . $users[$i] . "\r\n"; 
                unset($dates[$i]); 
                $dates = array_values($dates); 
                unset($users[$i]); 
                $users = array_values($users); 
                unset($ips[$i]); 
                $ips = array_values($ips); 
                unset($ports[$i]); 
                $ports = array_values($ports); 
                unset($data[$i]); 
                $data = array_values($data); 
                $i--; 
            } 
        } 
        echo "Waiting..., elements in array " . count($users) . "\r\n"; 
         
    } while(true); 


    function finduser($name) 
    { 
        global $users; 
         
        for ($i=0;$i<count($users);$i++) 
        { 
            if ($users[$i] == $name) 
            { 
                return $i; 
            } 
        } 

        return -1; 
    } 
     

?>
