#!/usr/bin/env bash

echo 'Creating cookie file...'

echo "Host: ${LINKCHECKER_HOST}"                         > /tmp/cookie.txt
echo "Set-cookie: user=\"${LINKCHECKER_USER}\""         >> /tmp/cookie.txt
if [[ -n "${LINKCHECKER_PASSWORD:-}" ]]; then
    echo "Set-cookie: password=\"${LINKCHECKER_PASSWORD}\"" >> /tmp/cookie.txt
fi

linkchecker_origin="${LINKCHECKER_ORIGIN:-http://${LINKCHECKER_HOST}}"

ignore_args=(
    --ignore-url="^(?!${linkchecker_origin}/${LINKCHECKER_PREFIX}).*"
    --ignore-url="^${linkchecker_origin}/(?:login|logout)(?:/|$)"
)

while IFS= read -r target; do
    if [[ -n "${target}" ]]; then
        escaped_target="$(printf '%s' "${target}" | sed 's/[][\\.^$*+?(){}|]/\\&/g')"
        ignore_args+=(--ignore-url="^${linkchecker_origin}/${LINKCHECKER_PREFIX}${escaped_target}(?:/|$)")
    fi
done <<< "${LINKCHECKER_EXCLUDE_TARGETS:-}"

linkchecker --verbose --output none "${ignore_args[@]}" \
    --file-output html/ascii/linkchecker-output.html \
    --cookiefile=/tmp/cookie.txt \
    "${linkchecker_origin}/${LINKCHECKER_PREFIX}"
