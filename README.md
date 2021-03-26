![logo](img/logo-hdm.png)

* [Goal](#goal)
* [Get Started](#get-started)
	* [Dependencies](#dependencies)
	* [Configuration](#configuration)
		* [Application Configuration File](#application-configuration-file)
		* [LDAP Configuration File](#ldap-configuration-file)
		* [NFS Configuration File](#nfs-configuration-file)
	* [Run it !](#run-it-)
		* [Docker Image](#docker-image)
		* [Helm Chart](#helm-chart)
		* [From Sources](#from-sources)
* [Screenshots & User Guide](#screenshots--user-guide)

# Goal

Site qui permet de visualiser les métriques de qualité de la donnée.

# Get Started

As you may have understood, Job orchestrator needs an **ecosystem** of application in order to work. It serves as a pass between all application's APIS.

## Dependencies

- Kubernetes Cluster `>=v1.15.15`

	- Rancher Installed `>=2.2.10` and API Endpoint accessible.
	- Need Rancher Project with rights to : [Check workload statues, API Access, Check logs].
	- Need a kubernetes Namespace with rights to : [deploy jobs, creates secrets, create configmaps].

- Gitlab `>=12.0.4`

	- Gitlab Installed and API Endpoint accessible.
	- Group Project containing Docker Runner Images repositories.
	- API ACCESS TOKEN for this particular group with rights to [ Read Registries, Read Repositories].

- Nexus `>=3.29.2-02`

	- Nexus Installed and API Endpoint accessible.
	- Default Repository
	- User / Password with rights to [Read artifacts, Search Queries]

## Configuration

### Application Configuration File

`/var/www/html/conf/appli/conf-appli.php` : 

```json
{

}
```

## Run it !

You can run job orchestrator from 3 different ways : 

### Docker Image 

To run anywhere : 

```bash
docker run -p 80:80 -v conf/:/var/www/html/conf/ health-data-metrics:latest
```

### Helm Chart

To deploy in production environments :

```bash
helm repo add curiedfcharts https://curie-data-factory.github.io/helm-charts
helm repo update

helm upgrade --install --namespace default --values ./my-values.yaml my-release curiedfcharts/hdm
```

More info [Here](https://artifacthub.io/packages/helm/curie-df-helm-charts/hdm)

### From sources

For dev purposes : 

1. Clone git repository :
```bash
git clone https://github.com/curie-data-factory/health-data-metrics.git
cd health-data-metrics/
```
2. Create Conf files & folders :
```bash
mkdir conf ldapconf
touch conf/appli/conf-appli.php
touch ldapconf/conf.php
```
3. Set configuration variables [see templates above](#configuration)
4. Then run the [Docker Compose](https://docs.docker.com/compose/) stack.

```bash
docker-compose up -d
```

5. Exec into the docker image

```bash
docker exec -it hdm /bin/bash
```

6. Resolve composer package dependencies. See [Here](https://getcomposer.org/doc/00-intro.md) for installing and using composer.

```bash
composer install --no-dev --optimize-autoloader
```

# Screenshots & User Guide

![home](img/capture-hdm1.PNG)
![explorator](img/capture-hdm2.PNG)
![rule-editor](img/capture-hdm3.PNG)