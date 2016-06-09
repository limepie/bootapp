Bootapp
=======

Bootapp은 Osx 환경에서 Docker를 사용한 PHP등의 개발 프로젝트에 도움을 준다.


Installation / Usage
--------------------

1. Download

   ```
   wget https://raw.githubusercontent.com/yejune/bootapp/master/bootapp.phar
   ```

2. Install

   ```
   mv bootapp.phar /usr/local/bin/bootapp
   ```

3. Create Project

   ```
   bootapp project:create [folderName]
   ```

4. Docker compose up

   ```
   bootapp docker-compose:up
   bootapp docker-compose:stop
   ```

5. Php Composer

   ```
   bootapp composer:install
   bootapp composer:update
   ```

6. Database migration

   ```
   bootapp migration:create [ClassName]
   bootapp migration:migrate
   ```

7. Deploy [not yet]

   Docker, git, rsync Support

   ```
   bootapp deploy:docker [environment]
   bootapp deploy:git [environment]
   bootapp deploy:rsync [environment]
   ```

Updating Bootapp
----------------

Running `bootapp selfupdate`
