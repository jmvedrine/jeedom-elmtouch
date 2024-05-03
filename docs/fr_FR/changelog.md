# 20/02/2018

- Première version béta

# 17/03/2018

- Passage à NodeJS v8

# 13/08/2019

- Compatibilité php 7.3 et font awesome 5
- Version minimum de Jeedom requise : 3.3

# 23/09/2019

- Passage à NodeJS v12

# 04/11/2019

- Tag "v4" pour le market

# 17/12/2020

- Note dans les docs sur le problème avec Debian  et raspbian 10 Buster et méthode pour le résoudre.

# 21/02/2022

- Passage de la librairie Nefit easy HTTP Server à la librairie plus récente Bosch XMPP.

# 31/08/2022

- Retour à librairie Nefit easy HTTP Server pour corriger le problème sur les commandes action.

# 19/10/2022

- Compatibilté avec Jeedom 4.3 onglet commande affichage de l'état.

# 11/04/2024

- Suppression des widgets personnalisés, le plugin utilise maintenant les widgets core.
Ceci devrait simplifier la compatibilité avec les évolutions de Jeedom.
Cela a nécessité quelques changements dans les commandes :
L'ancienne commande action string "Mode" s'appelle maintenant "Nom du mode" et une nouvelle commande info binaire "Mode" apparait qui vaut 1 si le mode est Programme (l'horloge) et 0 si le mode est Manuel (la main).
Les 2 commandes action "Mode horloge" et "Mode manuel" s'appellent maintenant "Activer Programme" et "Désactiver programme". Attention si vous les changez de nom le widget ne fonctionnera plus correctement.
- Nouvelle option dans la configuration du plugin "Vous laisser personnaliser entierement les widgets" si elle n'est pas cochée, lors de la sauvegarde de l'équipement ElmTouch, l'ordre des commandes est réorganisé automatiquement, si elle est cochée, le plugin vous laisse réorganiser les commandes dans le widget.
- Bouton pour afficher/masquer le mot de passe dans la configuration du plugin.
- Affichage des versions du plugin de NodeJS, de NPM, et de l'OS dans la configuration du plugin pour faciliter les demandes d'aide sur le forum.

# 03/05/2024

- Bouton dans la configuration pour créer un post sur le forum dans la bonne catégorie, avec le bon tag et des renseignements sur la configuration pour aider les autres utilisateurs à vous aider ;-)
- IMPORTANT : Correction d'une erreur dans le démarrage du démon. Je me suis rendu compte du problème à l'occasion de l'installation du plugin sur une machine vierge. 

Si vous n'arrivez pas à démarrer le démon, réinstallez les dépendances et relancez le démon.