
# Payload / Malware Detection

key="a760407018404d4e9219adf16da4ca02.inxX9x8PTZ7HZtrs0L7sEuGT"

ip_val=$( echo $1 | tr -d '.' | tr -d '"' )


mal_scan=$(
jq -n \
  --arg model "deepseek-v3.1:671b-cloud" \
  --arg system "Return ONLY 1 for malicious, 0 for clean. NOTHING ELSE." \
  --rawfile log apachelog \
  '
  {
    model: $model,
    messages: [
      { "role": "system", "content": $system },
      {
        "role": "user",
        "content": (
          "This is a web request. Check if it contains any malicious payload. " +
          "If yes return 1, if no return 0. NOTHING ELSE.\n" +
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


if [[ $mal_scan == "null" ]]; then
   curl -s -X PATCH -d '{"mal":711}' https://session-orbit-default-rtdb.firebaseio.com/$ip_val.json > db_log
   echo 711
else
   if [[ $mal_scan == "1" || $mal_scan == "0" ]]; then
      curl -s -X PATCH -d "{\"mal\":\"$mal_scan\"}" https://session-orbit-default-rtdb.firebaseio.com/$ip_val.json > db_log
      echo $mal_scan
   else
      curl -s -X PATCH -d '{"mal":755}' https://session-orbit-default-rtdb.firebaseio.com/$ip_val.json > db_log
      echo 711
   fi
fi


