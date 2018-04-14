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

Il faut entrer :

-   **Numéro de série** : le numéro de série à 9 chiffres (Serial) qui figure sur la notice et au dos du thermostat

-   **Clé d'accès** : la clé d'accès alphanumérique (Access) qui figure sur la notice et au dos du thermostat

-   **Mot de passe** : Le mot de passe que vous avez choisi lors de la création du compte sur le serveur de Bosch.

Et ne pas oublier de cliquer sur **Sauvegarder**.

Vérifier que l'installation des dépendances et le statut du démon sont bien OK.
Si le statut du démon est "NOK", il faut résoudre le problème avant de continuer car cela signifie
que le plugin ne peut pas communiquer avec le serveur Bosch et donc rien ne fonctionnera. 

Pour comprendre le problème :

- Si le statut des dépendances est NOK, consultez le log "Elmtouch_update" et résolvez le problème puis
relancez l'installation des dépendances

- Si le statut des dépendances est OK mais que le statut du démon est NOK, passez le niveau de log en "Debug"
puis relancez manuellement le démon. Si le démon est toujours "NOK", consultez le log "Elmtouch" pour comprendre le problème.
Vérifiez aussi vos numéro de série, code d'accès et mot de passe.

Vous pouvez demander de l'aide sur le forum https://www.jeedom.com/forum/viewtopic.php?f=143&t=34491

Les dépendances installent le module nodejs nefit-easy http server de Robert Klep  
(https://github.com/robertklep/nefit-easy-http-server).

Le démon se charge de le démarrer et de l'arrêter. Il faut que le numéro de série, le
code d'accès et le mot de passe soient corrects pour que le démon puisse fonctionner.

Création des équipement
===

Attention, pour le moment ce plugin ne peut gérer qu'un seul thermostat Elm Touch. 

Je pense qu'il serait sans doute
possible de gérer plusieurs thermostats en leur donnant à chacun un port différent pour le serveur. Mais je n'ai pas codé cela

Veillez à ne créer qu'un seul équipement !

Lors de la création en plus des champs habituels pour tout plugin Jeedom

-   **Nom de l'équipement ELM Touch** : nom de votre équipement ELM Touch

-   **Objet parent** : indique l’objet parent auquel appartient
    l’équipement

-   **Activer** : permet de rendre votre équipement actif

-   **Visible** : le rend visible sur le dashboard

-   **Coefficient de conversion** est utilisé pour la conversion des kWh en m<sup>3</sup>. En effet contrairement à la consommation
d'électricité qui est mesurée et facturée en kWh, la consommation de gaz est mesurée en m<sup>3</sup> et facturée en kWh.
Pour cette conversion votre fournisseur d'énergie utilise un **coefficient thermique** calculé par le responsable du réseau de distribution
(GRDF en France) et qui varie suivant la ville et le moment. En effet un m<sup>3</sup> de gaz suivant l'altitude, la provenance, la température, ... n'a pas le même pouvoir calorifique
et donc un coefficient variable est nécessaire. Le thermostat ELM Touch utilise en interne un coefficient de 8.125 kWh par m<sup>3</sup> qui est celui qui est utilisé par défaut
par le plugin. Mais vous pouvez le changer, normalement cette information doit figurer sur votre facture ou votre fournisseur doit pouvoir vous la fournir.

-   **Prix du gaz par kWh**  figure obligatoirement sur votre facture. Le plugin prend 5 centimes par kWh par défaut, ce qui ne correspond sans doute pas à votre cas.

-   **Auto-actualisation (cron)** Expression cron pour le rafraichissement des informations (Par défaut '*/5 * * * *'
    soit toutes les 5 minutes). Si vous ne connaissez pas la syntaxe des expressions cron, utilisez l'assistant.

Cliquez ensuite sur Sauvegarder, l'équipement est créé avec les commandes correspondantes.

Lors de la première sauvegarde de l'équipement, le plugin lance l'importattion toutes les consommations journalières enregistrées dans le thermostat
(ou la chaudière ?). Cette opération est faite à raison de 32 jours toutes les 15 minutes et peut donc durer un certain temps si 
votre thermostat est installé depuis longtemps.

Ces commandes étant historisées cela permet de disposer de courbes très intéressantes pour optimiser son chauffage.
Par la suite tous les jours les valeurs de la veille sont importées pendant la nuit par une tâche planifiée.

Cette importation est faite en utilisant deux nombres entrés dans la configuration de l'équipement pour convertir les valeurs stockées qui sont en kWh en mètres-cubes et en euros.

Elles sont importées par le plugin dès qu'elles sont disponibles dans le thermostat pendant la nuit. Je ne connais pas l'heure précises mais ce n'est pas à minuit pile,
c'est plus tard.

Si à un moment donné vous voulez relancer l'import de ces consommations, cliquez sur le bouton **Ré-importer les consommations** cela relance l'import
toujours à raison de 32 jours toutes les 15 minutes. Assurez vous que vos 2 coefficients sont corrects avant de relancer l'import.

Les commandes disponibles
===

En cliquant sur l'onglet commande vous accédez aux commandes disponibles

-   le nom affiché sur le dashboard

-   le type et le sous-type

-   historiser : permet d’historiser la donnée

-   Afficher pemret de l'afficher ou non sur le Dashboard.

-   configuration avancée (petites roues crantées) : permet d’afficher
    la configuration avancée de la commande (méthode
    d’historisation, widget…​)

-   Tester : permet de tester la commande

Le plugin est très jeune et cette liste est ammenée à s'enrichir.

Changer certains noms de commandes peut provoquer des dysfonctionnements.

| Nom                                  | Type    | Sous type  | Rôle                                                                                                                                                               |
| :--:                                 | :---:   | :---:      | :---:                                                                                                                                                              |
| **Consigne**                         | info    | numeric    | Donne la température de consigne. Associée à la commande action **Thermostat** . Normalement non affichée                                                          |
| **Thermostat**                       | action  | slider     | Permet de fixer la température de consigne en °C de 5 à 30°C                                                                                                       |
| **Température**                      | info    | numeric    | Donne la température ambiante mesurée par le thermostat en °C de 5 à 30°C                                                                                          |
| **Température extérieure**           | info    | numeric    | Donne la température extérieure en °C de -40 à +50°C mesurée par la sonde de la chaudière s'il y en a une, sinon récupérée sur Internet par le thermostat          |
| **Température eau de chauffage**     | info    | numeric    | Donne la température de l'eau dans le circuit de chauffage central en sortie de chaudière                                                                          |
| **Pression**                         | info    | numeric    | Donne la pression en bar dans le circuit de chauffage central entre 0 et 25 bars. Ne fonctionne pas avec toutes les chaudières                                     |
| **Eau chaude**                       | info    | binary     | Vaut 1 si l'eau chaude sanitaire est active et 0 sinon. Associée aux deux commandes action **hotwater_Off** et **hotwater_On**. Normalement non affichée           |
| **hotwater_Off**                     | action  | other      | Arrête la production d'eau chaude sanitaire pour le mode courant ("Mode horloge" ou "Mode manuel")                                                                 |
| **hotwater_On**                      | action  | other      | Met en marche la production d'eau chaude sanitaire pour le mode courant ("Mode horloge" ou "Mode manuel")                                                          |
| **Verrouillage**                     | action  | binary     | Renvoie 1 si la chaudière est verrouillée et 0 sinon. Associée aux deux commandes action **lock** et **unlock**. Normalement non affichée                          |
| **lock**                             | action  | other      | Verrouille la chaudière                                                                                                                                            |
| **unlock**                           | action  | other      | Déverrouille la chaudière                                                                                                                                          |
| **Mode**                             | info    | string     | Nom du mode actif (Mode manuel ou Mode horloge) associée aux deux commandes action **Mode manuel** et **Mode horloge**. Normalement non affichée                   |
| **Mode manuel**                      | action  | other      | Fait passer le thermostat en mode manuel (la main)                                                                                                                 |
| **Mode horloge**                     | action  | other      | Fait passer le thermostat en mode programme (l'horloge)                                                                                                            |
| **Chauffage actif**                  | info    | binary     | Vaut 1 si la chaudière est en fonctionnement pour le chauffage central (pas pour l'ECS). Utilisée par les plugins mobile et Homebridge                             |
| **Consommation annuelle**            | info    | numeric    | Donne la consommation de la chaudière en kWh depuis le 1er janvier de l'année en cours                                                                             |
| **Puissance**                        | info    | numeric    | Donne la puissance en W calculée sur la consommation dans les dernières minutes                                                                                    |
| **Consommation chauffage en kWh**    | info    | numeric    | Consommation journalière pour le chauffage central en kWh  . Disponibble le matin pour la veille seulement, pas pour le jour en cours                              |
| **Consommation chauffage en m3**     | info    | numeric    | Consommation journalière pour le chauffage central en m<sup>3</sup>  . Disponibble le matin pour la veille seulement, pas pour le jour en cours                    |
| **Consommation chauffage en euro**   | info    | numeric    | Consommation journalière pour le chauffage central en euros. Disponibble le matin pour la veille seulement, pas pour le jour en cours                              |
| **Consommation eau chaude en kWhh**  | info    | numeric    | Consommation journalière pour l'eau chaude sanitaire en kWh  . Disponibble le matin pour la veille seulement, pas pour le jour en cours                            |
| **Consommation eau chaude en m3**    | info    | numeric    | Consommation journalière pour l'eau chaude sanitaire en m<sup>3</sup>  . Disponibble le matin pour la veille seulement, pas pour le jour en cours                  |
| **Consommation eau chaude en euro**  | info    | numeric    | Consommation journalière pour l'eau chaude sanitaire en euros. Disponibble le matin pour la veille seulement, pas pour le jour en cours                            |
| **Consommation jour en kWh**         | info    | numeric    | Consommation journalière totale (chauffage + ECS) en kWh  . Disponibble le matin pour la veille seulement, pas pour le jour en cours                               |
| **Consommation jour en m3**          | info    | numeric    | Consommation journalière totale (chauffage + ECS) en  m<sup>3</sup>  . Disponibble le matin pour la veille seulement, pas pour le jour en cours                    |
| **Consommation jour en euro**        | info    | numeric    | Consommation journalière totale (chauffage + ECS) en  euros. Disponibble le matin pour la veille seulement, pas pour le jour en cours                              |
| **Température extérieure moyenne**   | info    | numeric    | Température extérieure moyenne journalière (sonde ou Internet)                                                                                                     |
| **Etat bruleur**                     | info    | binary     | Vaut 1 si le brûleur est allumé (pour le chauffage ou l'eau chaude sanitaire), 0 sinon                                                                             |
| **Nom etat bruleur**                 | info    | string     | Traduit **Etat bruleur** en chaine de caractères 0 = Arrêté et 1 = Chauffage. Utilisé par les plugin mobile et Hombridge                                           |
| **Etat chaudière**                   | info    | string     | Vaut "Chauffage", "Eau chaude" ou "Arrêt" suivant l'état de la chaudière                                                                                           |


Panel desktop
===

Le plugin dispose d'un panel desktop dans le menu Accueil.

FAQ 
===

Quelle est la fréquence de rafraichissement ?

Par défaut le plugin recupère les informations toutes les minutes.

Je voudrais récupérer les informations avec une fréquence plus grande est-ce possible ?

Sur la page Equipement, modifiez la valeur du champ **Auto-actualisation (cron)**
Si vous ne connaissez pas la syntaxe des expressions cron, utilisez l'assistant.

Il faut noter que le plugin émet deux requêtes vers le serveur Bosch à chaque
récupération. En cas d'abus il est à craindre que le compte ne soit bloqué.

De plus accroitre la fréquence de récupération accroit aussi les chances d'une collision
lors de l'emploi simultané du plugin et de l'application sur mobile.

Enfin vu l'inertie, les températures extérieurs et intérieures ne sont pas des grandeurs à variation rapide !

