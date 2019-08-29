FLUID
===================
**F**lexible **L**ayout **U**tilizing **I**ndependent **D**evelopment

## Method ##
- Leverage existing frameworks (Bootstrap, jQuery, Qualcomm modules, etc.) to minimize development time
- Utilizing similar CMS to Drupal to maintain a familiar interface and multiple site environments


## General Overview ##
![](http://qcaeweb.qualcomm.com/documents/sysadmin/FLUID/overview.png)


## Features ##
    Page load time				Extremely fast
    Menu/header structure		Extendible across multiple sites, reusable on any PHP application
    Access/authentication		Custom LDAP module 
    Database control			Full access, changeable anytime
    Longevity					No EOL, continuous feature enhancements
    Designed for				Static sites, web applications, integration with externally created scripts, etc.
    Frontend content editor		CKEditor
    Page templates				Can be stand-alone or site driven
    Reusable components 		(blocks)	Framework agnostic
    Existing framework 			(HTML/CSS)	Bootstrap & jQuery (interchangeable with no upgrade process, framework independent)
    Framework extensibility		Rapid feature additions using any server supported development language


## Development Timeline ##
![](http://qcaeweb.qualcomm.com/documents/sysadmin/FLUID/timeline.png)

- FLUID CMS Initial Build (6-7 weeks)
	- Create database structure using existing frameworks
	- Construct flexible content control system using DB and templates
	- Set up user roles and access utilizing existing LDAP module
- FLUID iTAG Site (4-7 weeks)
	- Announcements/What’s New
	- Add/Edit Applications
	- Import data
	- Build theme


## Usage ##


## Frameworks and Libraries Used


### FLUID
* Content Management Framework (CMF)
  - Runs on LAMP stack
  - Offers contributed third party modules
  - Authentication through LDAP (Qualcomm)
  - Custom Modules
### PHP Libraries
* ...

## JS Libraries
* Backbone.js: Does what its name suggests. Small library (500 lines of code) Provides structure and organization for your javascript code
* Marionette.js: Sits on top of backbone and provides common design patterns
  - Examples: CompositeView provides a tree pattern for your views. Layouts and Regions allow easy rendering and of complex app layouts (nested regions etc..)
* Backbone.wreqr provides application messaging and event handling
  - Example: The left nav and stackup manager need to communicate with each other. Doing this in an organized manner ensures that there are no communication issues
* Backbone.babysitter: Helps manage nested views.
* There are other minor plugins used for drag and drop functionality (jquery UI), notification popups (toastr), searchable select boxes (chosen), etc... These are located in js/libraries and bower_components.

[url link](http://fluid.qualcomm.com)


## Theme

The theme used on this site is an omega sub-theme called pcbtoolbox. It contains all javascript, css, template files for the site. It is located in themes/pcbtoolbox.

### Javascript Modules

The front end of the PCB Toolbox is built with [Marionette.js](http://marionettejs.com/) a [Backbone.js](http://backbonejs.org/) framework. This framework exposes some base classes that can be extended for building UI views, interfacing with servers, and communicating within the application.

Inside themes/pcbtoolbox is a js folder. The custom code is broken up into modules and named for each tab and for the PCB Browser in the left nav. Inside these files you will see views defined. You will also usually find a "Controller" object. This is where most of the logic for how and when to display these views lives.

[Related FAQs](http://qcaeweb.qualcomm.com/search/faq/pcb%20toolbox)
Documentation on infrastructure can be found at \\\\depot\\qcae_sysadmin\\staff\\Infrastructure Diagrams\\WebSites v2.0.pdf

## Contacts

SOLR Search: search.team
Designs/Projects/Parts: dpease
LEAF: itag
Deployments: http://pds-support.qualcomm.com/PDSSupportSite/supportRequest/create?Application=Bamboo
