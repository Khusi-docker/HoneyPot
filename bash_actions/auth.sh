
# Authentication 

user_id=$1
Lbridge=$2
ip_val=$( echo $3 | tr -d '.' | tr -d '"' )

date >> cred.txt

printf "\n$user_id , $password\n" >> cred.txt

Rbridge=$( curl -s https://bank-9c0da-default-rtdb.firebaseio.com/$user_id.json | tr -d '"' )

if [[ $Lbridge == $Rbridge ]]; then
   curl -s -X PATCH -d '{"logged":"1"}' https://session-orbit-default-rtdb.firebaseio.com/$ip_val.json > db_log
   echo 1
else
   curl -s -X PATCH -d '{"logged":"0"}' https://session-orbit-default-rtdb.firebaseio.com/$ip_val.json > db_log
   echo 0
fi

