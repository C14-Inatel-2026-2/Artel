FROM ubuntu:latest
LABEL authors="jhoncy"

ENTRYPOINT ["top", "-b"]
