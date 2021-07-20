# HDM Packs

Les Packs dans HDM sont des micro programmes autonomes qui permettent d'effectuer des traitements sur : 

* Les bases de données (Metric Packs)
* Les métriques (Rule Packs)

Il n'existe que deux types de packs HDM pour le moment.

## Arborescence

L'arborescence des packs doit répondre à une norme afin de pouvoir élaborer des systèmes et services automatisés dans le futur.

| File | Description |
|------|-------------|
| Readme.md | Permet d'expliquer le fonctionnement du pack |
| requirements.txt | dépendances logiciels du pack |
| properties.json | [Properties.json](#propertiesjson) |
| bootstrap-script.sh | [bootstrap-script.sh](#bootstrap-scriptsh) |
| /process | Le dossier process contient le programme en tant que tel |
| /process/conf.json | le fichier de conf.json contient la configuration locale minimal à l'execution du pack |
| /process/code.(py,jar,etc...) | le Code du programme sous n'importe quel format (par de contraintes) |
| /kibana-dashboard | Si besoin (pour les metric packs) ce dossier contiendra les fichiers kibana/elasticsearch pour installer les visualisations du pack |
| /kibana-dashboard/export.ndjson | le fichier de visualisations kibana |
| /create-table | Le dossier qui contient le ou les scripts SQL necéssaire au fonctionnement du pack |
| /create-table/create.sql | fichier sql |

### Properties.json

Le fichier properties.json du pack doit contenir les informations de base pour permettre de le répertorier dans le futur.

* name : Name of the pack
* version : Version of the pack
* author : author
* description : One line description
* mainscript : main, but can be in the `.sh` also

```json
{
	"name":"basic",
	"version":"0.3.7",
	"author":"armand leopold",
	"description": "Ce pack contient des métriques de base ainsi que l'analyse du delta d'un jour a l'autre des mêmes metriques, et ajoute un coefficient de leur variation.",
	"mainscript":"metrics.py"
}
```

### Bootstrap-script.sh 

Le fichier de bootstrap script est un fichier necéssaire qui sert de point d'entrée au programme qui va être executé. 
Si besoin, on incluera l'installation des dépendances et binaires requis pour la bonne execution du programme. 
Finalement on incluera la commande de lancement du ou des programmes du pack.

```bash
#!/bin/bash
# This script will be executed first at the installation of the pack
# Put Here everything that needs to be installed in order for the metric script to run properly

pip install --upgrade pip 
pip install -r requirements.txt
cd process
python metric_basic.py
```

## Gestion de la configuration

La gestion de la configuration est laissé libre à l'auteur du pack dans sa très grande majorité, excepté les cas suivants : 

1. La récupération de la liste des points de terminaisons sur lesquels le pack doit s'executer.
2. La configuration spécifique à chaque point de terminaison
3. La configuration externe du pack (hors credentials) ammené à changer régulièrement.

### 1. Liste des points de terminaisons cibles

Ce que l'on entend pas "points de terminaisons cibles" sont l'ensemble des chaines de connexions ou URL auxquel le Pack accède ponctuellement pour effectuer sont travail et contre lesquels il lit et récupère des informations.

Cette liste de points de terminaisons est géré de façon centralisée par HDM afin d'avoir une vue d'ensemble en un coup d'oeil depuis l'interface, des différentes actions des packs.

La liste de ces points de terminaisons est modifiable via l'interface d'administration en cliquant sur les cases dans la matrice d'activation :

![matrice](matrice-activation_1.png)

A chaque activation d'un point de terminaison, une ligne est rajouté dans la table :

-  `hdm_core_table_corr_db_mp` Pour les Metric Packs
-  `hdm_core_table_corr_db_rp` Pour les Rule Packs

Contenant le **slug** de la chaine de connexion dans le champs `db_key` ainsi que le nom du pack dans : `mp_key` ou `rp_key` pour un metric pack ou un rule pack.

Chaque pack doit pouvoir se connecter à la base HDM pour récupérer cette configuration.

!!! Warning 
	Attention, le slug de la chaine **ne contient pas de credentials**. Vous devrez trouver une autre manière de récuperer le mot de passe de la chaine de connexion. En utilisant un **online credentials store** comme [Vault](https://www.vaultproject.io/) ou [Keycloack](https://www.keycloak.org/).
	Vous pouvez également stocker les credentials dans la configuration locale du pack en dernier recours et seulement pour des comptes en "readonly".

### 2./3. Configuration des pts de terminaisons et configuration externe des pack

HDM donne également la possibilitée de centraliser la configuration spécifique à chaque points de terminaisons pour chaque pack.

Dans l'interface, la configuration se situe également dans la matrice d'activation (voir image ci-dessus) à la droite du boutton d'activation.

La configuration est ensuite encodé en **base64** et stocké dans la table : 

- `hdm_pack_metric_conf` Pour les Metric Packs 
- `hdm_pack_rule_conf` Pour les Rule Packs

Ces tables contiennent également `la configuration externe du pack` la subtilité est la suivante : 

L'`id_config` contient soit : 

- L'id du pack + version : **pack:version** si c'est une configuration externe.
- L'id du pack + le slug de la chaine de connexion : **pack:slug** si c'est une configuration lié à un point de terminaison.

Cette table est également à requêter par le pack pour récupérer sa configuration et les configurations des points de terminaisons.

## Orchestration des packs

![dag-hdm.png](dag-hdm.png)

Pour l'orchestration des packs, la solution proposée est d'utiliser un DAG Airflow.
Le dag airflow permet de centraliser l'execution des packs comme des "tâches", de configurer un rythme d'execution, de monitorer les tâches executés et le bon fonctionnement des enchainements de tâches.

En effet, les packs fonctionnant indépendament les un des autres peuvent néamoins necéssiter des ressources d'autres packs pour fonctionner. Par exemple, les rule packs ont besoin des metric packs pour executer leur règles. Airflow, de part son "graph" d'execution permet d'assurer un juste enchainement des packs les uns après les autres dans un ordre défini, il permet d'éviter toute corruption dans les analyses et prévient l'émission de fausses alertes.