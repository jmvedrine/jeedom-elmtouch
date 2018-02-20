Description
===

Jeedom plugin allowing to control smart thermostats made by Bosch connected to boiler controlled by an Heatronic 3 or 4 thermostat .

This thermostat is made by Bosch and sold according to countries under different names:

- Nefit Easy (Netherlands)
- Junkers Control CT100 ( Belgium)
- Buderus Logamatic TC100 ( Belgium)
- E.L.M. Touch (France)
- Worcester Wave ( England)
- Bosch Control CT-100 ( Other countries)

As the equipment is the same and  Bosch's server common to all countries, this plugin can be also used in all countries

Note: the plugin does not communicate directly with the thermostat, it request the server who in turn request the thermostat.
It would be possible with an electronic interface to design another plugin working completely locally connected on the EMS bus at the boiler's connectors
or at the thermostat's connectors.
After some thinking I did not proceed in this way, but if one day the Bosch server become suddenly been inalienable or if Bosch restrict connections,
this solution would remain.

To activate the plugin it is necessary that your account on the Bosch server is already created with a password
and it is necessary that the thermostat is online and working.

Plugin configuration
===

Please check that the dependences installation and the deamon status are OK.

The dependences install the nodejs module nefit-easy HTTP server from Robert Klep (https://github.com/robertklep/nefit-easy-http-server).

The deamon start it and stop it.

It is necessary that the serial number, the access code and the password are correct so that the demon can work.

It is necessary to enter:

- ** Serial number **: the serial number with 9 digits (Serial) which appears on the note and on the back of thermostat

- ** Access key **: the alphanumeric access key (Access) which appears on the note and on the back of thermostat

- ** Password **: The password you choose during the account creation on the Bosch server.

And not to forget to click on ** Saving **.

Equipment creation
===

Be careful, currently this plugin can only manage one thermostat.

I think that it would be
possible to manage several thermostats giving to each one a different port for the easy-server (currently the port default to 3000, maybe I should make it configurable ?)
. But I did not code that

Take care to one create only one equipment!

During creation besides the usual fields for all Jeedom plugins

-   ** Name of ELM Touch equipment**: name you want to give to your thermostat

-   ** Parent object **: indicate the parent object to which the equipment belongs

-   ** To activate **: allows to make your equipment active

-   ** Visible **: makes the equipement visible on the dashboard

-   ** Auto--actualization (cron) ** Expression cron for the refresh of informations (By default '*/5 *** *'
that is to say every 5 minutes).

If you don't know the syntax of cron expressions, use the assistant.

Click then on Saving, the equipment is created with the corresponding commands.

While clicking on the equipment, you find all the details.

In lower part you find the list of the commands:

-   the name displayed on the dashboard

-   historise: allow to historise the data

-   advanced configuration (small gear): allows to display
advanced configuration of the command (method
of historisation, widget…)

-   To test: allows to test the command

FAQ
 ===

What is the refreshing frequency ?

By default the plugin recovers information every 5 minutes.

Is it possible to refresh informations more frequently ?

On the Equipment page, modify the value of the field ** Auto--actualization (cron) **
If you do not know the syntax of the cron expressions, use the assistant.

It should be noted that the plugin emits two requests to the Bosch server with each
refreshing. In the event of an abuse it is to be feared that the account would be blocked.

Moreover increase the refresh frequency increases also the chances of a collision
in case of simultaneous use of the plugin and mobile application.

Finally considering inertia, the outside and room temperatures don't have fast variations !
