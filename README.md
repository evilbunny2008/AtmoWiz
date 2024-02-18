# Intro
AtmoWiz is a set of scripts, both in python and php, to graph and control your aircon or heat pump by accessing and utilising data returned by the Sensibo API.

The Sensibo client python code based on the [Sensibo Python SDK](https://github.com/Sensibo/sensibo-python-sdk/) and [Pull Requests Authors](https://github.com/Sensibo/sensibo-python-sdk/pulls). The client script is a basic script to interogate the Sensibo API, can toggle the AC on/off, set the target temperature and so on.

The AtmoWiz Daemon script is a python script which downloads data exposed by the API and store it in a local database. sensibo.conf can be copied to /etc and chmod 600 to protect the apikey and database password.

The AtmoWiz PHP scripts are used to display the data as a line graphs and can turn your AC on and off, either by manual intervention or by setting a time or condition based setting to automatically turn your aircon on and off or change modes.

[Click here](https://github.com/evilbunny2008/AtmoWiz/wiki) to jump to the installation instructions on the wiki.

# Issues and Feature Requests

Issues and feature requests can be filed [here](https://github.com/evilbunny2008/AtmoWiz/issues)

# Credits

* [favicon.svg came from svgrepo.com](https://www.svgrepo.com/svg/268208/cooling-cooler)
* [Graphs by CanvasJS.com](https://canvasjs.com/)
* [Arrow Icons](https://svgsilh.com/00bcd4/image/34285.html)
* [Flaticon Icons](https://www.flaticon.com)
* [Multiple Sites Provide Free Observations](https://github.com/evilbunny2008/AtmoWiz/wiki/Sources-of-Weather-Observations)

# Screen Shots

![Screen Shot0](https://raw.githubusercontent.com/evilbunny2008/AtmoWiz/master/screenshots/ss0.png)
![Screen Shot1](https://raw.githubusercontent.com/evilbunny2008/AtmoWiz/master/screenshots/ss1.png)
![Screen Shot2](https://raw.githubusercontent.com/evilbunny2008/AtmoWiz/master/screenshots/ss2.png)
![Screen Shot3](https://raw.githubusercontent.com/evilbunny2008/AtmoWiz/master/screenshots/ss3.png)
![Screen Shot4](https://raw.githubusercontent.com/evilbunny2008/AtmoWiz/master/screenshots/ss4.png)
![Screen Shot5](https://raw.githubusercontent.com/evilbunny2008/AtmoWiz/master/screenshots/ss5.png)
![Screen Shot6](https://raw.githubusercontent.com/evilbunny2008/AtmoWiz/master/screenshots/ss6.png)
![Screen Shot7](https://raw.githubusercontent.com/evilbunny2008/AtmoWiz/master/screenshots/ss7.png)
![Screen Shot8](https://raw.githubusercontent.com/evilbunny2008/AtmoWiz/master/screenshots/ss8.png)
![Screen Shot9](https://raw.githubusercontent.com/evilbunny2008/AtmoWiz/master/screenshots/ss9.png)
