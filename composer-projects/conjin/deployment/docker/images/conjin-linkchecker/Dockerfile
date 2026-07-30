ARG BASE_IMAGE
FROM ${BASE_IMAGE}

COPY ./startup.sh /startup.sh
RUN chmod +x /startup.sh

ENTRYPOINT [ "/startup.sh" ]
WORKDIR /mnt
