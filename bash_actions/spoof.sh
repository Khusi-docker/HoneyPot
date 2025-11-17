
# Spoofing Detection

# ipquality api

api_key=KxNz4yY3GIfeLOPI9HcOsNFUSR2ws6JJ

ip=$1

ip_val=$( echo $1 | tr -d '.' | tr -d '"' )
ts=$( date )
curl -s -X PATCH -d "{\"IP_V4\":\"$1\"}" https://session-orbit-default-rtdb.firebaseio.com/$ip_val.json > db_log
curl -s -X PATCH -d "{\"time\":\"$ts\"}" https://session-orbit-default-rtdb.firebaseio.com/$ip_val.json > db_log



spoof_state=$( curl -s "https://ipqualityscore.com/api/json/ip/KxNz4yY3GIfeLOPI9HcOsNFUSR2ws6JJ/$ip" | jq -r '.vpn' )

if [ "$spoof_state" == "true" ]; then
   echo "1"
   curl -s -X PATCH -d '{"spoof":"1"}' https://session-orbit-default-rtdb.firebaseio.com/$ip_val.json > db_log
else
   echo "0"
   curl -s -X PATCH -d '{"spoof":"0"}' https://session-orbit-default-rtdb.firebaseio.com/$ip_val.json > db_log
fi








