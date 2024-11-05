# Docker Install

while in the docker folder, build the image

```bash
cd ./docker
docker build -t web_control .
```

then run the image
```
docker run -d --name web_control -p 8080:8080 -p 34291:34291/udp -p 34292:3429/udp2 -p 34293:34293/udp -p 34294:34294/udp -p 34295:34295/udp -p 34296:34296/udp -p 34297:34297/udp -p 34298:34298/udp -p 34299:34299/udp web_control
```
