#!/usr/bin/php
<?php

/**
 * Place a user or users into an organization. User can be a single user id or and array of ids
 *
 * @param int $user_id (can be array of user id's), int $org_id (organizations id)
 * @return boolean (success or not)
 */
function userToOrg($user_id, $org_id){
    global $key, $base_url;
    $ids = NULL;
    $import_url = '';
    if(is_array($user_id)){
        foreach($user_id as $value){
            if(!empty($ids))
                $ids .= ",$value";
            else
                $ids = $value;
        }
    }else{
        $ids = $user_id;
    }
    $import_url = $base_url."orgs/$org_id/accounts/add";
    $curl = curl_init();
    curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $import_url, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => "ids=$ids&key=$key"));

    $result = curl_exec($curl);
    curl_close($curl);
    echo var_dump($result); // need to put this result to log

    if($result){
        $result = json_decode($result);
        if(is_object($result) && $result->success == "true")
            return TRUE;
        else
            return FALSE;
    }else{
        return FALSE;
    }

}

/**
 * Place a user or users into a group. User can be a single user id or and array of ids
 *
 * @param int $user_id (can be array of user id's), int $group_id (groups id)
 * @return boolean (success or not)
 */
function userToGroup($user_id, $group_id){
    global $key, $base_url;
    $ids = NULL;
    $import_url = '';
    if(is_array($user_id)){
        foreach($user_id as $value){
            if(!empty($ids))
                $ids .= ",$value";
            else
                $ids = $value;
        }
    }else{
        $ids = $user_id;
    }
    $import_url = $base_url."groups/$group_id/accounts/add";
    echo $import_url;
    $curl = curl_init();
    curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $import_url, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => "ids=$ids&key=$key"));

    $result = curl_exec($curl);
    curl_close($curl);
    echo var_dump($result); // need to put this result to log

    if($result){
        $result = json_decode($result);
        if(is_object($result) && $result->success == "true")
            return TRUE;
        else
            return FALSE;
    }else{
        return FALSE;
    }

}

function getGroupMembers($group_id) {
  global $key, $base_url;
  $curl = curl_init();
  //get organization members by organization id
  curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $base_url."groups/$group_id/accounts?key=$key"));
  $group_members = curl_exec($curl);
  if($group_members){
    $group_members = json_decode($group_members);
  }else{
    $group_members = FALSE;
  }
  curl_close($curl);
  return $group_members;
}

function getAllOrganizations(){
  global $key, $base_url;
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  
  //Request list of all orginizations
  curl_setopt($curl, CURLOPT_URL, $base_url."orgs?key=$key");

  $all_org = curl_exec($curl);

  if($all_org){
    $all_org = json_decode($all_org);
  }else{
    $all_org = FALSE;
  }
  
  curl_close($curl);
  return $all_org;
}

function getOrgByID($org_id){
  global $key, $base_url;
  $curl = curl_init();
  //get organization by orgsync id
  curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $base_url."orgs/$org_id?key=$key"));
  $org = curl_exec($curl);
  if($org){
    $org = json_decode($org);
  }else{
    $org = FALSE;
  }
  curl_close($curl);
  return $org;
}

function getOrgMembers($org_id){
  global $key, $base_url;
  $curl = curl_init();
  //get organization members by organization id
  curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $base_url."orgs/$org_id/accounts?key=$key"));
  $org_members = curl_exec($curl);
  if($org_members){
    $org_members = json_decode($org_members);
  }else{
    $org_members = FALSE;
  }
  curl_close($curl);
  return $org_members;
}

/**
 * Remove an account or multiple accounts from an organization.  $ids can be one id or and array of ids.
 *
 *
 */
function removeAccount($user_ids, $org_id){
    global $key, $base_url;
    $url = $base_url."/orgs/$org_id/accounts/remove";
    $curl = curl_init();
    $count = 0;	 
    $ids = '';
    if(is_array($user_ids)){
        foreach($user_ids as $value){
            if(!empty($ids))
                $ids .= ',';
            $ids .= $value;
            $count++;
            if($count >=300){ // orgsync can't hadle large groups of remove so limit it to 300 per api call
                curl_setopt_array($curl, array(CURLOPT_TIMEOUT => 900, CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $url, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => "ids=$ids&key=$key"));           
                $result = curl_exec($curl); // need to handle error checking here and log the event if these individual calls fail.
                $ids = '';
                $count = 0;
            }
        }
    }else{
    $ids = $user_ids;
    }

    curl_setopt_array($curl, array(CURLOPT_TIMEOUT => 900, CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $url, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => "ids=$ids&key=$key"));           
    $result = curl_exec($curl); 
    curl_close($curl);
    echo var_dump($result); // need to put this in log
    if($result){
        $result = json_decode($result);
        if(is_object($result) && $result->success == "true")
            return TRUE;
        else
            return FALSE;
        
    }
}

function removeGroupAccount($user_ids, $group_id){
    global $key, $base_url;
    $ids = '';
    $count = 0;
    $url = $base_url."/groups/$group_id/accounts/remove";
    $curl = curl_init();
// orgsync's server can't handle large add or removes.  We are going to send chunks of 300
    if(is_array($user_ids)){
        foreach($user_ids as $value){
            if(!empty($ids))
                $ids .= ',';
            $ids .= $value;
            $count++;
            if($count >=300){
                curl_setopt_array($curl, array(CURLOPT_TIMEOUT => 900, CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $url, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => "ids=$ids&key=$key"));           
                $result = curl_exec($curl); // need to handle error checking here and log the event if these individual calls fail.
                $ids = '';
                $count = 0;
            }
        }
    }
    curl_setopt_array($curl, array(CURLOPT_TIMEOUT => 900, CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $url, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => "ids=$ids&key=$key"));
    $result = curl_exec($curl); 
    curl_close($curl);
    echo var_dump($result); // need to put this in log
    if($result){
        $result = json_decode($result);
        if(is_object($result) && $result->success == "true")
            return TRUE;
        else
            return FALSE;
        
    }
}

/**
 * Add an account to OrgSync.  Remember that you must be setup for SSO and know the proper
 * username format for your university.  Usually is the email but it could be different.
 *
 *
 */
function addAccount($username, $first_name, $last_name, $student_id=NULL, $send_welcome=FALSE){
    global $key, $base_url;
    
    $json_data = array("username" => $username, "send_welcome" => $send_welcome, "account_attributes" => array("email_address" => $username, "first_name" => $first_name, "last_name" => $last_name),"identification_card_numbers" => array($student_id));
//    $json_data = array("username" => $username, "send_welcome" => true, "account_attributes" => array("email_address" => $username, "first_name" => $first_name, "last_name" => $last_name));
    $json_data = json_encode($json_data);
    $url = $base_url."/accounts?key=$key";
    $curl = curl_init();
    curl_setopt_array($curl, array(CURLOPT_TIMEOUT => 900, CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $url, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => $json_data));           
    $result = curl_exec($curl); 
    curl_close($curl);
    if($result){
        $result = json_decode($result);
        if(!empty($result->id)){
            return $result->id;
        }else{
            echo var_dump($result); //need to write this to log instead of echo
            return FALSE;
        }
    }
}

/**
 * Remove an account to OrgSync. 
 *
 *
 */
function deleteAccount($account_id){
    global $key, $base_url;
    
    $json_data = array("account_id" => $account_id);
    $json_data = json_encode($json_data);
    $url = $base_url."/accounts/$account_id?key=$key";
    $curl = curl_init();
    curl_setopt_array($curl, array(CURLOPT_CUSTOMREQUEST => "DELETE", CURLOPT_TIMEOUT => 900, CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $url));           
    $result = curl_exec($curl); 
    curl_close($curl);
    if($result){
        $result = json_decode($result);
        if($result->$success){
            return TRUE;
        }else{
            echo var_dump($result); //need to write this to log instead of echo
            return FALSE;
        }
    }
}

function getIDFromUsername($username){
    global $key, $base_url;    

    $curl = curl_init();
    curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $base_url."accounts/username/$username?key=$key"));
    $result = curl_exec($curl);
    curl_close($curl);
  
    if($result){
        $result = json_decode($result);
        if(!empty($result->id))
            return $result->id;
    }
    
    return false;
    
}

function getAccountFromUsername($username){
    global $key, $base_url;    

    $curl = curl_init();
    curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $base_url."accounts/username/$username?key=$key"));
    $result = curl_exec($curl);
    curl_close($curl);
  
    if($result){
        $result = json_decode($result);
        return $result;
    }
    
    return false;
    
}

function getAccountFromEmail($email){
    global $key, $base_url;    

    $curl = curl_init();
    curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $base_url."accounts/email/$email?key=$key"));
    $result = curl_exec($curl);
    curl_close($curl);
  
    if($result){
        $result = json_decode($result);
        return $result;
    }
    
    return false;
    
}

function getIDFromEmail($email){
    global $key, $base_url;    

    $curl = curl_init();
    curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $base_url."accounts/email/$email?key=$key"));
    $result = curl_exec($curl);
    curl_close($curl);
  
    if($result){
        $result = json_decode($result);
        if(!empty($result->id))
            return $result->id;
    }
    
    return false;
    
}

function getAccountByID($id){
  global $key, $base_url;
  $curl = curl_init();
  curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $base_url."accounts/$id?key=$key"));
    $account_result = curl_exec($curl);
    
  if($account_result)
    $account_result = json_decode($account_result);
  else
    $account_result = FALSE;
  
  curl_close($curl);  
  return $account_result;
}

function getAllAccounts(){
  global $key, $base_url;
  $curl = curl_init();
  //Request list of all accounts
  curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $base_url."accounts?key=$key"));
  $all_accounts = curl_exec($curl);
  if($all_accounts){
    $all_accounts = json_decode($all_accounts);
  }else{
    $all_accounts = FALSE;
  }

  return $all_accounts;
  curl_close($curl);
}

/**
 * Get banner id from orgsync by email address
 * 
 * @param type $email
 * @return boolean
 */
function getBannerIDFromEmail($email){
  $parts = explode("@", $email);
  $username = strtolower($parts[0]);
  if(!empty($username)){
    $query = "SELECT * FROM sdr_member WHERE username='$username' ORDER BY id DESC";
    $result = pg_query($query);
    if($result && pg_num_rows($result) > 0){
      $row = pg_fetch_assoc($result);
      return $row['id'];
    }else{
      return false;
    }
  }else{
    return false;
  }
}

function getAccountByBannerID($banner_id){
  global $key, $base_url,$banner_profile_id;
  $curl = curl_init();

  curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $base_url."accounts/custom_profile/$banner_profile_id/$banner_id?key=$key"));
  $account_result = curl_exec($curl);

  if($account_result)

      $account_result = json_decode($account_result);
  else
    $account_result = FALSE;
  curl_close($curl);  
  return $account_result;

}

/**
 * This retrieves a student from banner by banner id
 * @global type $banner_base_url
 * @param type $email
 * @param type $banner_id
 * @return student object
 */
function getStudentFromBanner($email, $banner_id){
    global $banner_base_url;
    if(empty($banner_id)){
        $email = explode('@',$email);
        $user_id = $email[0];
    }else{
        $user_id = $banner_id;
    }
    $curl = curl_init();
    curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $banner_base_url."student/$user_id"));
    $result = curl_exec($curl);
    curl_close($curl);
    $student = json_decode($result);
    return $student;
}

/**
 * This retrieves all students from banner
 * @global type $banner_base_url
 * @return student list
 */
function getAllStudentsFromBanner(){
    global $banner_base_url;
    $curl = curl_init();
    curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $banner_base_url."student"));
    $result = curl_exec($curl);
    curl_close($curl);
    $students = json_decode($result);
    return $students;
}

function getIDCardByAccountID($account_id){
  global $key, $base_url;
  $curl = curl_init();
  curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $base_url."identification_cards/account_id/$account_id?key=$key"));
    $card_result = curl_exec($curl);
    
  if($card_result)
    $card_result = json_decode($card_result);
  else
    $card_result = FALSE;
  
  curl_close($curl);  
  return $card_result;
}

function getIDCardByID($id){
  global $key, $base_url;
  $curl = curl_init();
  curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $base_url."identification_cards/$id?key=$key"));
    $card_result = curl_exec($curl);
    
  if($card_result)
    $card_result = json_decode($card_result);
  else
    $card_result = FALSE;
  
  curl_close($curl);  
  return $card_result;
}

function getIDCardByBannerID($banner_id){
  global $key, $base_url;
  $curl = curl_init();
  curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $base_url."identification_cards/number/$banner_id?key=$key"));
    $card_result = curl_exec($curl);
    
  if($card_result)
    $card_result = json_decode($card_result);
  else
    $card_result = FALSE;
  
  curl_close($curl);  
  return $card_result;
}

function addIDCard($account_id, $banner_id){
  global $key, $base_url;

    $json_data = array("account_id" => $account_id, "number" => $banner_id);
    $json_data = json_encode($json_data);
    $url = $base_url."/identification_cards?account_id=$account_id&number=$banner_id&key=$key";
    $curl = curl_init();
    curl_setopt_array($curl, array(CURLOPT_TIMEOUT => 900, CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $url, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => $json_data));           
    $card_result = curl_exec($curl);
    
  if($card_result)
    $card_result = json_decode($card_result);
  else
    $card_result = FALSE;
  
  curl_close($curl);  
  return $card_result;
}

function removeIDCard($card_id){
    global $key, $base_url;
    
    $url = $base_url."/identification_cards/$card_id?key=$key";
    $curl = curl_init();
    curl_setopt_array($curl, array(CURLOPT_CUSTOMREQUEST => "DELETE", CURLOPT_TIMEOUT => 900, CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $url));           
    $result = curl_exec($curl); 
    curl_close($curl);
    if($result){
        $result = json_decode($result);
        if(!property_exists($result, 'message')){
            return TRUE;
        }else{
            return FALSE;
        }
    }
}

?>
