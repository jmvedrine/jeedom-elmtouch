Description 
===

Plugin permettant de contrôler les thermostats Elm touch vendu par ELM Leblanc.

Ce thermostat est fabriqué par Bosch et vendu suivant les pays sous divers noms :

-   Nefit Easy (Pays Bas)
-   Junkers Control CT100 (Belgique)
-   Buderus Logamatic TC100 (Belgique)
-   E.L.M. Touch (France)
-   Worcester Wave (Angleterre)
-   Bosch Control CT‑100 (Autres pays)

Comme le matériel est le même et le serveur Bosch commun à tous les pays,
ce plugin peut également être utilisé dans tous les pays

Note : le plugin ne communique pas directement avec le thermostat,
il interroge le serveur Bosch qui à son tour interroge le thermostat.
Il serait possible avec un circuit d'interface de concevoir un autre plugin
qui fonctionne entièrement en local branché sur le bus EMS soit au niveau
de la chaudière soit au niveau du thermostat. Après réflexion je n'ai pas poursuivi
dans cette voie, mais si un jour le serveur Bosch venait à être indisponible
ou si Bosch restreignait la connexion, il resterait cette solution.

Avant d'activer le plugin il faut que votre compte sur le serveur de Bosch soit créé avec un mot de passe
et il faut que le thermostat fonctionne.

Configuration du plugin 
===

Vérifier que l'installation des dépendances et le statut du démon sont bien OK.

Les dépendances installent le module nodejs nefit-easy http server de Robert Klep  
(https://github.com/robertklep/nefit-easy-http-server).

Le démon se charge de le démarrer et de l'arrêter. Il faut que le numéro de série, le
code d'accès et le mot de passe soient corrects pour que le démon puisse fonctionner.

Il faut entrer :

-   **Numéro de série** : le numéro de série à 9 chiffres (Serial) qui figure sur la notice et au dos du thermostat

-   **Clé d'accès** : la clé d'accès alphanumérique (Access) qui figure sur la notice et au dos du thermostat

-   **Mot de passe** : Le mot de passe que vous avez choisi lors de la création du compte sur le serveur de Bosch.

Et ne pas oublier de cliquer sur **Sauvegarder**.

Création des équipement
===

Attention, pour le moment ce plugin ne peut gérer qu'un seul thermostat Elm Touch. 

Je pense qu'il serait sans doute
possible de gérer plusieurs thermostats en leur donnant à chacun un port diffrérent pour le serveur. Mais je n'ai pas codé cela

Veillez à ne créer qu'un seul équipement !

Lors de la création en plus des champs habituels pour tout plugin Jeedom

-   **Nom de l'équipement ELM Touch** : nom de votre équipement ELM Touch

-   **Objet parent** : indique l’objet parent auquel appartient
    l’équipement

-   **Activer** : permet de rendre votre équipement actif

-   **Visible** : le rend visible sur le dashboard

-   **Auto-actualisation (cron)** Expression cron pour le rafraichissement des informations (Par défaut '*/5 * * * *'
    soit toutes les 5 minutes).

Si vous ne connaissez pas la syntaxe des expressions cron, utilisez l'assistant.

Cliquez ensuite sur Sauvegarder, l'équipement est créé avec les commandes correspondantes.

En cliquant sur l'équipement, vous retrouvez tous les détails.

En dessous vous retrouvez la liste des commandes :

-   le nom affiché sur le dashboard

-   historiser : permet d’historiser la donnée

-   configuration avancée (petites roues crantées) : permet d’afficher
    la configuration avancée de la commande (méthode
    d’historisation, widget…​)

-   Tester : permet de tester la commande

Les commandes disponibles
===
Le plugin est très jeune et cette liste est ammenée à s'enrichir.
Changer certains noms provoque des dysfonctionnements.

-   **Consigne**Type : info Sous-type : numeric Rôle : Donne la température de consigne. Associée à la commande action**Thermostat** n'est normalement pas affichée
-   **Thermostat** Type : action Sous type : slider Rôle : Permet de fixer la température de consigne en °C de 5 à 30°C
-   **Température** Type : info Sous-type : numeric Rôle : Donne la température ambiante mesurée par le thermostat en °C de 5 à 30°C
-   **Température extérieure** Type : info Sous-type : numeric Rôle : Donne la température extérieure en °C de -40 à +50°C mesurée par la sonde de la chaudière s'il y en a une, sinon récupérée sur Internet par le thermostat
-   **Température eau de chauffage** Type : info Sous-type : numeric Rôle : Donne la température de l'eau dans le circuit de chauffage central en sorie de chaudière
-   **Eau chaude**Type : info Sous-type : binaire Rôle : vaut 1 si l'eau chaude est active et 0 sinon. Assiciée aux deux commandes action**hotwater_Off**et**hotwater_On**
-   **hotwater_Off** Type : action Sous type :other Rôle : arrête la production d'eau chaude pour le mode courant ("Mode horloge" ou "Mode manuel")
-   **hotwater_On** Type : action Sous type :other Rôle : met en marche la production d'eau chaude pour le mode courant ("Mode horloge" ou "Mode manuel")
-   **Verrouillage**Type : action Sous-type : binaire Rôle : Renvoit 1 si la chaudière est verrouillée et 0 sinon. Associée aux deux commandes action**lock**et**unlock**
-   **lock** Type : action Sous-type : other Rôle : verrouille la chaudière
-   **unlock** Type : action Sous-type : other Rôle : déverrouille la chaudière

Lors de l'enregistrement de l'équipement, le plugin importe toutes les consommations journalières enregistrées dans le thermostat
(ou la chaudière ?). Cette opération est afire à raison de 32 jours toutes les 15 minutes et peut donc durer un certain temps si 
votre thermostat est installé depusi longtemps.

Ces commandes étant historisées cela permet de disposer de courbes très intéressantes pour optimiser son chauffage.
Cette importation est faite en utilisant deux nombres entrés dans la configuration de l'équipement pour convertir les valeurs stockées qui sont en kWh en mètres-cubes et en euros.

-   **Coefficient de conversion** est utilisé pour la conversion des kWh en euros. En effet contrairement à la consommation
d'électricité qui est mesurée et facturée en kWh, la consommation de gaz est mesurée en m3 et facturée en kWh.
Pour cette conversion votre fornisseur d'énergie utilise un **coefficient thermique** calculé par le responsable du réseau de distribution
(GRDF en France) et qui varie suivant la ville et le moment. En effet un m3 de gaz suiavnt l'altitude, la provenance, la température, ... n'a pas le même pouvoir calorifique
et donc un coefficient variable est nécessaire. Le thermosta ELM Touch utilise en interne un coefficient de 8.125 kWh par m3 qui est celui qui est utilisé par défaut
par le plugin. Mais vous pouvez le changer, normalement cette information doit figurer sur votre facture ou votre fournisseur doit pouvoir vous la fournir.

-   **Prix du gaz par kWh**  figure obligatoirement sur votre facture. Le plugin prend 5 centimes par kWh par défaut ce qui ne correspond certainement pas à votre cas.

Une fois l'importation terminée vous disposez des commandes info historisées suivantes

-   **Consommation jour chauffage** : Consommation journalière pour le chauffage central en kWh
-   **Consommation jour eau chaude** : Consommation journalière pour l'eau chaude domestique en kWh
-   **Consommation jour totale** : Consommation journalière totale en kWh
-   **Consommation chauffage en m3** : Consommation journalière pour le chauffage central en m3
-   **Consommation eau chaude en m3** : Consommation journalière pour l'eau chaude domestique en m3
-   **Consommation jour en m3** : Consommation journalière totale en m3
-   **Consommation chauffage en euro** : Consommation journalière pour le chauffage central en m3
-   **Consommation eau chaude en euro** : Consommation journalière pour l'eau chaude domestique en euros
-   **Consommation jour en euro** : Consommation journalière totale en m3

Attention si vous affichez les valeurs courantes sur le desktop, ne perdez pas de vue que ces valeurs concernent **la veille** et pas aujourd'hui ! 
Elles sont importées par le plugin dès qu'elles sont disponibles dans le thermostat pendant la nuit. Je ne connais pas l'heure précises mais ce n'est pas à minuit pile,
cest plus tard.

FAQ 
===

Quelle est la fréquence de rafraichissement ?

Par défaut le plugin recupère les informations toutes les 5 min.

Je voudrais récupérer les informations avec une fréquence plus grande est-ce possible ?

Sur la page Equipement, modifiez la valeur du champ **Auto-actualisation (cron)**
Si vous ne connaissez pas la syntaxe des expressions cron, utilisez l'assistant.

Il faut noter que le plugin émet deux requêtes vers le serveur Bosch à chaque
récupération. En cas d'abus il est à craindre que le compte ne soit bloqué.

De plus accroitre la fréquence de récupération accroit aussi les chances d'une collision
lors de l'emploi simultané du plugin et de l'application sur mobile.

Enfin vu l'inertie, les températures extérieurs et intérieures ne sont pas des grandeurs à variation rapide !

