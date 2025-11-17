# Scanning Detection

key="a760407018404d4e9219adf16da4ca02.inxX9x8PTZ7HZtrs0L7sEuGT"

ip=$1
cat /var/log/apache2/access.log | grep $ip | tail -n 30 | tr -d '"' > apachelog
cat /var/log/apache2/access.log | grep $2 | tail -n 30 | tr -d '"' >> apachelog

ip_val=$( echo $ip | tr -d '.' | tr -d '"' )

net_scan=$(
jq -n \
  --arg model "deepseek-v3.1:671b-cloud" \
  --arg system "You are a professional scanning detector. Only output 1 or 0." \
  --rawfile log apachelog \
  '
  {
    model: $model,
    messages: [
      { "role": "system", "content": $system },
      {
        "role": "user",
        "content": (
          "Check this. If you detect any scanning software used for scanning, output 1 or 0 only. " +
          "If true output 1, else output 0. Ignore ngrok and serveo.net. " +
          "Here is the log:\n" +
          $log
        )
      }
    ]
  }
  ' \
| curl -s https://ollama.com/api/chat \
    -H "Authorization: Bearer $key" \
    -H "Content-Type: application/json" \
    --data @- \
| jq -r '.message.content'
)

net_scan=$(
jq -n \
  --arg model "deepseek-v3.1:671b-cloud" \
  --arg system "You are a professional scanning detector. Only output 1 or 0." \
  --rawfile log apachelog \
  '
  {
    model: $model,
    messages: [
      { "role": "system", "content": $system },
      {
        "role": "user",
        "content": ( 
          "Check this. If you detect any scanning software used for scanning, output 1 or 0 only. " +
          "If true output 1, else output 0. Ignore ngrok and serveo.net. " +
          "Here is the log:\n" +
          $log
        )
      }
    ]
  }
  ' \
| curl -s https://ollama.com/api/chat \
    -H "Authorization: Bearer $key" \
    -H "Content-Type: application/json" \
    --data @- \
| jq -r '.message.content'
)

if [[ $net_scan == "null" ]]; then
   curl -s -X PATCH -d '{"scan":711}' https://session-orbit-default-rtdb.firebaseio.com/$ip_val.json > db_log 
   echo 711   
else
   if [[ $net_scan == "1" || $net_scan == "0" ]]; then
      curl -s -X PATCH -d "{\"scan\":\"$net_scan\"}" https://session-orbit-default-rtdb.firebaseio.com/$ip_val.json > db_log 
      echo $net_scan 
   else
      curl -s -X PATCH -d '{"scan":755}' https://session-orbit-default-rtdb.firebaseio.com/$ip_val.json > db_log 
      echo 711
   fi
fi


