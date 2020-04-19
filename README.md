# Install on ubuntu

```
apt install software-properties-common
```

```
docker run -it -v $(pwd):/var/www/public -p 80:80 docker.artron.net/micro-service/http-proxy
```

```
docker build -t docker.artron.net/micro-service/http-proxy .
```