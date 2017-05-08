.PHONY: test-docker
test-docker:
	@service beanstalkd start > /dev/null && \
	service mysql start > /dev/null && \
	service rabbitmq-server start > /dev/null && \
	service redis-server start > /dev/null && \
	mysql -u root -e 'CREATE DATABASE database_name;' && \
	mysql -u root database_name < contrib/schema-mysql.sql && \
	mysql -u root -e "CREATE USER 'travis'@'127.0.0.1' IDENTIFIED BY '';" && \
	mysql -u root -e "GRANT ALL PRIVILEGES ON database_name.* TO 'travis'@'127.0.0.1' WITH GRANT OPTION;" && \
	mysql -u root -e "DELETE FROM mysql.user WHERE User=''; FLUSH PRIVILEGES;" && \
	mysqladmin -u root password password && \
	service postgresql start && \
	su postgres -c "createdb database_name" && \
	su postgres -c "psql -d database_name -f contrib/schema-pgsql.sql" && \
	su postgres -c "psql database_name -c \"CREATE USER travis password 'asdf12';\"" && \
	su postgres -c "psql database_name -c \"ALTER ROLE travis WITH Superuser;\"" && \
	su postgres -c "psql database_name -c \"GRANT ALL PRIVILEGES ON DATABASE database_name TO travis;\"" && \
	cp phpunit.xml.dist phpunit.xml && \
	composer install --dev && \
	vendor/bin/phpcs --standard=psr2 src/ && \
	vendor/bin/phpmd src/ text cleancode,codesize,controversial,design,naming,unusedcode && \
	vendor/bin/phpunit


.PHONY: test-docker-rabbitmq
test-docker-rabbitmq:
	@service rabbitmq-server start > /dev/null && \
	cp phpunit.xml.dist phpunit.xml && \
	vendor/bin/phpunit tests/josegonzalez/Queuesadilla/Engine/RabbitmqEngineTest.php

.PHONY: test-56
test-56:
	docker build -f contrib/56.Dockerfile -t queuesadilla-test-56 .
	docker run --rm -v $(PWD):/app queuesadilla-test-56 make test-docker

.PHONY: test-7
test-7:
	docker build -f contrib/7.Dockerfile -t queuesadilla-test-7 .
	docker run --rm -v $(PWD):/app queuesadilla-test-7 make test-docker
