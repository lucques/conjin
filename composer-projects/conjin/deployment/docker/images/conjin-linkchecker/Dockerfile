FROM ghcr.io/linkchecker/linkchecker:10.6.0

COPY ./startup.sh /startup.sh
RUN chmod +x /startup.sh

ENTRYPOINT [ "/startup.sh" ]
WORKDIR /mnt