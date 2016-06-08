FROM alpine:3.2

ENV ELASTICMQ_VERSION 0.9.3
EXPOSE 9324

COPY custom.conf /elasticmq/custom.conf
COPY logback.xml /elasticmq/logback.xml

RUN apk --update add openjdk7-jre-base
ADD https://s3-eu-west-1.amazonaws.com/softwaremill-public/elasticmq-server-${ELASTICMQ_VERSION}.jar /elasticmq/server.jar

CMD ["java", "-jar", "-Dconfig.file=/elasticmq/custom.conf", "-Dlogback.configurationFile=/elasticmq/logback.xml", "/elasticmq/server.jar"]
