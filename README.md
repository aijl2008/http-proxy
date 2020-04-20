# 构建并推送镜像

```
rm -rf cache/*
rm -rf vendor
docker run -it --rm -v $(pwd):/var/www docker.artron.net/library/phpswoole/swoole:4.4.16-php7.4 composer install -vvv
docker build -t docker.artron.net/micro-service/http-proxy .
docker push docker.artron.net/micro-service/http-proxy
```

# 启动

```
docker run -p 9501:9501 docker.artron.net/micro-service/http-proxy
```