
ssh_state=$( service --status-all | grep ssh | cut -c4 )
apache2_state=$( service --status-all | grep apache2 | cut -c4 )

if [ "$ssh_state" == "-" ]; then
   echo -e "\e[31m Starting Sevice.ssh"
   service ssh start
else
   echo -e "\e[32m SSH Running"
fi

if [ "$apache2_state" == "-" ]; then
   echo -e "\e[31m Starting Service.apache2\e[0m"
   service apache2 start
else
   echo -e "\e[32m Apache2 Running\e[0m"
fi

sleep 3

#!/bin/bash

# Menu options
options=("Serveo SSH Hosting" "Localhost.run Hosting" "Ngrok TCP 80")
selected=0

# Hide cursor
tput civis

draw_menu() {
  clear
  echo "Select Hosting Method:"
  echo "-----------------------"
  for i in "${!options[@]}"; do
    if [[ $i == $selected ]]; then
      echo -e " ➤ \e[1;32m${options[$i]}\e[0m"
    else
      echo "   ${options[$i]}"
    fi
  done
}

while true; do
  draw_menu

  read -rsn1 key
  if [[ $key == $'\e' ]]; then
    read -rsn2 key2

    case "$key2" in
      "[A") # UP
        ((selected--))
        (( selected < 0 )) && selected=$((${#options[@]} - 1))
        ;;
      "[B") # DOWN
        ((selected++))
        (( selected >= ${#options[@]} )) && selected=0
        ;;
    esac

  elif [[ $key == "" ]]; then
    # ENTER pressed
    break
  fi
done

# Restore cursor
tput cnorm
clear

# Execute selection
case $selected in
  0)
    echo "Selected: Serveo Hosting"
    echo "Starting Serveo tunnel..."
    ssh -R 80:localhost:80 serveo.net
    ;;
  1)
    echo "Selected: Localhost.run Hosting"
    echo "Starting localhost.run tunnel..."
    ssh -R 80:localhost:80 ssh.localhost.run
    ;;
  2)
    echo "Selected: Ngrok TCP 80"
    echo "Starting Ngrok TCP tunnel..."
    ngrok http 80
    ;;
esac
