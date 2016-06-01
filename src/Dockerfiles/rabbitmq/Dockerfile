FROM debian:jessie

RUN apt-get update && apt-get install -y rabbitmq-server

# Clean up APT when done.
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# configure rabbitmq
RUN echo "[{rabbit, [{loopback_users, []}]}]." > /etc/rabbitmq/rabbitmq.config && \
    service rabbitmq-server start && \
    rabbitmq-plugins enable rabbitmq_management && \
    service rabbitmq-server stop

# runtime configuration
ENTRYPOINT service rabbitmq-server start && while true; do sleep 1d; done

# expose ports
EXPOSE 5672
EXPOSE 15672