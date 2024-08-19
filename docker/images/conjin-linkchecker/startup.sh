#!/usr/bin/env bash

echo 'Creating cookie file...'

echo "Host: ${LINKCHECKER_HOST}"                         > /tmp/cookie.txt
echo "Set-cookie: user=\"${LINKCHECKER_USER}\""         >> /tmp/cookie.txt
echo "Set-cookie: password=\"${LINKCHECKER_PASSWORD}\"" >> /tmp/cookie.txt

linkchecker --verbose --file-output html/ascii/linkchecker-output.html --cookiefile=/tmp/cookie.txt --ignore-url="^(?!http://${LINKCHECKER_HOST}/${LINKCHECKER_PREFIX}).*" http://${LINKCHECKER_HOST}/${LINKCHECKER_PREFIX}