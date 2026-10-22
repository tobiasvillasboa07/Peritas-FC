FROM php:8.3-cli
ADD https://github.com/benbjohnson/litestream/releases/download/v0.3.13/litestream-v0.3.13-linux-amd64.tar.gz /tmp/litestream.tar.gz
RUN tar -C /usr/local/bin -xzf /tmp/litestream.tar.gz && rm /tmp/litestream.tar.gz
WORKDIR /app
COPY . .
RUN cp litestream.yml /etc/litestream.yml
CMD ["sh", "/app/run.sh"]