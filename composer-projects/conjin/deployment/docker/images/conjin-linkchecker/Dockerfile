ARG BASE_IMAGE=ghcr.io/linkchecker/linkchecker:10.6.0
FROM ${BASE_IMAGE}

COPY ./startup.sh /startup.sh
COPY ./linkcheckerrc /etc/linkchecker/linkcheckerrc
RUN chmod +x /startup.sh

ENTRYPOINT [ "/startup.sh" ]
WORKDIR /mnt
