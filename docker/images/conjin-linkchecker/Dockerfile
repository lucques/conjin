FROM ghcr.io/linkchecker/linkchecker:latest

COPY ./startup.sh /startup.sh
RUN chmod +x /startup.sh

ENTRYPOINT [ "/startup.sh" ]
WORKDIR /mnt