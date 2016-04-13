# auth #

### `GET|POST` /api/v1/auth ###

_Try to validate the user from the request and return a jwt authentication result then._

Try to validate the user from the request and return a jwt authentication result then.

#### Response ####

status:

  * type: choice
  * description: OK or unauthorized

token:

  * type: string
  * description: The JWT (only if status ok).

acl[]:

  * type: string
  * description: The roles of the authenticated user.

username:

  * type: string
  * description: The username of the authenticated user.



# files #

### `GET` /api/v1/AppKernel.php ###

_Retrieve the AppKernel.php._

Retrieve the AppKernel.php.


### `PUT` /api/v1/AppKernel.php ###

_Update the AppKernel.php with the given data if it is valid._

#### Response ####

status:

  * type: string
  * description: Either OK or ERROR

error:

  * type: object
  * description: Only present when the data contains parse errors

error[line]:

  * type: string
  * description: The line number containing the error

error[msg]:

  * type: string
  * description: The PHP parse error message


### `GET` /api/v1/composer.json ###

_Retrieve the composer.json._

Retrieve the composer.json.


### `PUT` /api/v1/composer.json ###

_Update the composer.json with the given data if it is valid._

#### Response ####

status:

  * type: string
  * description: Either OK or ERROR

error[]:

  * type: string
  * description: List of contained errors

warning[]:

  * type: string
  * description: List of contained warnings



# install #

### `GET` /api/v1/install/autoconfig ###

_Install time - auto config._

#### Response ####

php_cli:

  * type: string
  * description: The PHP interpreter to run on command line.

php_cli_arguments:

  * type: string
  * description: Command line arguments to add.


### `POST` /api/v1/install/configure ###

_Configure tenside._

#### Parameters ####

credentials:

  * type: object
  * required: true
  * description: The credentials of the admin user.

credentials[secret]:

  * type: string
  * required: true
  * description: The secret to use for encryption and signing.

credentials[username]:

  * type: string
  * required: true
  * description: The name of the admin user.

credentials[password]:

  * type: string
  * required: false
  * description: The password to use for the admin.

configuration:

  * type: object
  * required: false
  * description: The application configuration.

configuration[php_cli]:

  * type: string
  * required: false
  * description: The PHP interpreter to run on command line.

configuration[php_cli_arguments]:

  * type: string
  * required: false
  * description: Command line arguments to add.

#### Response ####

token:

  * type: string
  * description: The API token for the created user


### `POST` /api/v1/install/create-project ###

_Create a project._

#### Parameters ####

project:

  * type: object
  * required: true
  * description: The project to install.

project[name]:

  * type: string
  * required: true
  * description: The name of the project to install.

project[version]:

  * type: string
  * required: false
  * description: The version of the project to install (optional).

#### Response ####

task:

  * type: string
  * description: The id of the created install task


### `GET` /api/v1/install/get_state ###

_This method provides information about the installation._

#### Response ####

state:

  * type: object

state[tenside_configured]:

  * type: string
  * description: Flag if tenside has been completely configured.

state[project_created]:

  * type: string
  * description: Flag determining if a composer.json is present.

state[project_installed]:

  * type: string
  * description: Flag determining if the composer project has been installed (vendor present).

status:

  * type: string
  * description: Either OK or ERROR

message:

  * type: string
  * description: The API error message if any (only present when status is ERROR)


### `GET` /api/v1/install/search-project/{vendor}/{project} ###

_Retrieve the available versions of a package._

#### Requirements ####

**vendor**

  - Requirement: [\-\_a-zA-Z0-9]+
  - Type: string
  - Description: The vendor name of the package.
**project**

  - Requirement: [\-\_a-zA-Z0-9]+
  - Type: string
  - Description: The name of the package.

#### Response ####

versions[]:

  * type: object
  * description: The list of versions

versions[][name]:

  * type: string
  * description: The name of the package

versions[][version]:

  * type: string
  * description: The version of the package

versions[][version_normalized]:

  * type: string
  * description: The normalized version of the package

versions[][reference]:

  * type: string
  * description: The optional reference


### `GET` /api/v1/install/selftest ###

_Install time - self test._

#### Response ####

results[]:

  * type: object
  * description: The test results.

results[][name]:

  * type: string
  * description: The name of the test

results[][state]:

  * type: choice
  * description: The test result state.

results[][message]:

  * type: string
  * description: The detailed message of the test result.

results[][explain]:

  * type: string
  * description: Optional description that could hint any problems and/or explain the error further.



# misc #

### `POST` /api/v1/constraint ###

_Try to validate the version constraint._

Try to validate the version constraint.

#### Parameters ####

constraint:

  * type: string
  * required: true
  * description: The constraint to test.

#### Response ####

status:

  * type: choice
  * description: OK or ERROR

error:

  * type: string
  * description: The error message (if any).



# package #

### `GET` /api/v1/packages ###

_Retrieve the package list._

Retrieve the package list.

#### Filters ####

all:

  * Description: If present, all packages will get listed, only directly required ones otherwise.

#### Response ####

package name 1...n:

  * type: object
  * description: The content of the packages

package name 1...n[name]:

  * type: string
  * description: The name of the package

package name 1...n[version]:

  * type: string
  * description: The version of the package

package name 1...n[constraint]:

  * type: string
  * description: The constraint of the package (when package is installed)

package name 1...n[type]:

  * type: string
  * description: The noted package type

package name 1...n[locked]:

  * type: string
  * description: Flag if the package has been locked for updates

package name 1...n[time]:

  * type: datetime
  * description: The release date

package name 1...n[upgrade_version]:

  * type: string
  * description: The version available for upgrade (optional, if any)

package name 1...n[description]:

  * type: string
  * description: The package description

package name 1...n[license][]:

  * type: string
  * description: The licenses

package name 1...n[keywords][]:

  * type: string
  * description: The keywords

package name 1...n[homepage]:

  * type: string
  * description: The support website (optional, if any)

package name 1...n[authors][]:

  * type: object
  * description: The authors

package name 1...n[authors][][name]:

  * type: string
  * description: Full name of the author (optional, if any)

package name 1...n[authors][][homepage]:

  * type: string
  * description: Email address of the author (optional, if any)

package name 1...n[authors][][email]:

  * type: string
  * description: Homepage URL for the author (optional, if any)

package name 1...n[authors][][role]:

  * type: string
  * description: Author's role in the project (optional, if any)

package name 1...n[support][]:

  * type: object
  * description: The support options

package name 1...n[support][][email]:

  * type: string
  * description: Email address for support (optional, if any)

package name 1...n[support][][issues]:

  * type: string
  * description: URL to the issue tracker (optional, if any)

package name 1...n[support][][forum]:

  * type: string
  * description: URL to the forum (optional, if any)

package name 1...n[support][][wiki]:

  * type: string
  * description: URL to the wiki (optional, if any)

package name 1...n[support][][irc]:

  * type: string
  * description: IRC channel for support, as irc://server/channel (optional, if any)

package name 1...n[support][][source]:

  * type: string
  * description: URL to browse or download the sources (optional, if any)

package name 1...n[support][][docs]:

  * type: string
  * description: URL to the documentation (optional, if any)

package name 1...n[abandoned]:

  * type: boolean
  * description: Flag if this package is abandoned

package name 1...n[replacement]:

  * type: string
  * description: Replacement for this package (optional, if any)


### `GET` /api/v1/packages/{vendor}/{package} ###

_Retrieve a package._

Retrieve a package.

#### Requirements ####

**vendor**

  - Requirement: [\-\_a-zA-Z0-9]+
  - Type: string
  - Description: The name of the vendor.
**package**

  - Requirement: [\-\_a-zA-Z0-9]+
  - Type: string
  - Description: The name of the package.

#### Response ####

name:

  * type: string
  * description: The name of the package

version:

  * type: string
  * description: The version of the package

constraint:

  * type: string
  * description: The constraint of the package (when package is installed)

type:

  * type: string
  * description: The noted package type

locked:

  * type: string
  * description: Flag if the package has been locked for updates

time:

  * type: datetime
  * description: The release date

upgrade_version:

  * type: string
  * description: The version available for upgrade (optional, if any)

description:

  * type: string
  * description: The package description

license[]:

  * type: string
  * description: The licenses

keywords[]:

  * type: string
  * description: The keywords

homepage:

  * type: string
  * description: The support website (optional, if any)

authors[]:

  * type: object
  * description: The authors

authors[][name]:

  * type: string
  * description: Full name of the author (optional, if any)

authors[][homepage]:

  * type: string
  * description: Email address of the author (optional, if any)

authors[][email]:

  * type: string
  * description: Homepage URL for the author (optional, if any)

authors[][role]:

  * type: string
  * description: Author's role in the project (optional, if any)

support[]:

  * type: object
  * description: The support options

support[][email]:

  * type: string
  * description: Email address for support (optional, if any)

support[][issues]:

  * type: string
  * description: URL to the issue tracker (optional, if any)

support[][forum]:

  * type: string
  * description: URL to the forum (optional, if any)

support[][wiki]:

  * type: string
  * description: URL to the wiki (optional, if any)

support[][irc]:

  * type: string
  * description: IRC channel for support, as irc://server/channel (optional, if any)

support[][source]:

  * type: string
  * description: URL to browse or download the sources (optional, if any)

support[][docs]:

  * type: string
  * description: URL to the documentation (optional, if any)

abandoned:

  * type: boolean
  * description: Flag if this package is abandoned

replacement:

  * type: string
  * description: Replacement for this package (optional, if any)


### `PUT` /api/v1/packages/{vendor}/{package} ###

_Update the information of a package in the composer.json._

#### Requirements ####

**vendor**

  - Requirement: [\-\_a-zA-Z0-9]+
  - Type: string
  - Description: The name of the vendor.
**package**

  - Requirement: [\-\_a-zA-Z0-9]+
  - Type: string
  - Description: The name of the package.

#### Parameters ####

name:

  * type: string
  * required: true
  * description: The name of the package

constraint:

  * type: string
  * required: true
  * description: The constraint of the package (when package is installed)

locked:

  * type: string
  * required: true
  * description: Flag if the package has been locked for updates

#### Response ####

name:

  * type: string
  * description: The name of the package

version:

  * type: string
  * description: The version of the package

constraint:

  * type: string
  * description: The constraint of the package (when package is installed)

type:

  * type: string
  * description: The noted package type

locked:

  * type: string
  * description: Flag if the package has been locked for updates

time:

  * type: datetime
  * description: The release date

upgrade_version:

  * type: string
  * description: The version available for upgrade (optional, if any)

description:

  * type: string
  * description: The package description

license[]:

  * type: string
  * description: The licenses

keywords[]:

  * type: string
  * description: The keywords

homepage:

  * type: string
  * description: The support website (optional, if any)

authors[]:

  * type: object
  * description: The authors

authors[][name]:

  * type: string
  * description: Full name of the author (optional, if any)

authors[][homepage]:

  * type: string
  * description: Email address of the author (optional, if any)

authors[][email]:

  * type: string
  * description: Homepage URL for the author (optional, if any)

authors[][role]:

  * type: string
  * description: Author's role in the project (optional, if any)

support[]:

  * type: object
  * description: The support options

support[][email]:

  * type: string
  * description: Email address for support (optional, if any)

support[][issues]:

  * type: string
  * description: URL to the issue tracker (optional, if any)

support[][forum]:

  * type: string
  * description: URL to the forum (optional, if any)

support[][wiki]:

  * type: string
  * description: URL to the wiki (optional, if any)

support[][irc]:

  * type: string
  * description: IRC channel for support, as irc://server/channel (optional, if any)

support[][source]:

  * type: string
  * description: URL to browse or download the sources (optional, if any)

support[][docs]:

  * type: string
  * description: URL to the documentation (optional, if any)

abandoned:

  * type: boolean
  * description: Flag if this package is abandoned

replacement:

  * type: string
  * description: Replacement for this package (optional, if any)



# search #

### `POST` /api/v1/search ###

_Search for packages._

Search for packages.

#### Parameters ####

keywords:

  * type: string
  * required: true
  * description: The name of the project to search or any other keyword.

version:

  * type: string
  * required: false
  * description: The name of the project to install.

type:

  * type: choice
  * required: false
  * description: The type of package to search (optional, default: all).

threshold:

  * type: string
  * required: false
  * description: The amount of results after which the search shall be stopped (optional, default: 20).

#### Response ####

package name 1...n:

  * type: object
  * description: The content of the packages

package name 1...n[name]:

  * type: string
  * description: The name of the package

package name 1...n[version]:

  * type: string
  * description: The version of the package

package name 1...n[constraint]:

  * type: string
  * description: The constraint of the package (when package is installed)

package name 1...n[type]:

  * type: string
  * description: The noted package type

package name 1...n[locked]:

  * type: string
  * description: Flag if the package has been locked for updates

package name 1...n[time]:

  * type: datetime
  * description: The release date

package name 1...n[upgrade_version]:

  * type: string
  * description: The version available for upgrade (optional, if any)

package name 1...n[description]:

  * type: string
  * description: The package description

package name 1...n[license][]:

  * type: string
  * description: The licenses

package name 1...n[keywords][]:

  * type: string
  * description: The keywords

package name 1...n[homepage]:

  * type: string
  * description: The support website (optional, if any)

package name 1...n[authors][]:

  * type: object
  * description: The authors

package name 1...n[authors][][name]:

  * type: string
  * description: Full name of the author (optional, if any)

package name 1...n[authors][][homepage]:

  * type: string
  * description: Email address of the author (optional, if any)

package name 1...n[authors][][email]:

  * type: string
  * description: Homepage URL for the author (optional, if any)

package name 1...n[authors][][role]:

  * type: string
  * description: Author's role in the project (optional, if any)

package name 1...n[support][]:

  * type: object
  * description: The support options

package name 1...n[support][][email]:

  * type: string
  * description: Email address for support (optional, if any)

package name 1...n[support][][issues]:

  * type: string
  * description: URL to the issue tracker (optional, if any)

package name 1...n[support][][forum]:

  * type: string
  * description: URL to the forum (optional, if any)

package name 1...n[support][][wiki]:

  * type: string
  * description: URL to the wiki (optional, if any)

package name 1...n[support][][irc]:

  * type: string
  * description: IRC channel for support, as irc://server/channel (optional, if any)

package name 1...n[support][][source]:

  * type: string
  * description: URL to browse or download the sources (optional, if any)

package name 1...n[support][][docs]:

  * type: string
  * description: URL to the documentation (optional, if any)

package name 1...n[abandoned]:

  * type: boolean
  * description: Flag if this package is abandoned

package name 1...n[replacement]:

  * type: string
  * description: Replacement for this package (optional, if any)

package name 1...n[installed]:

  * type: string
  * description: Amount of installations

package name 1...n[downloads]:

  * type: string
  * description: Amount of downloads

package name 1...n[favers]:

  * type: string
  * description: Amount of favers



# selftest #

### `GET` /api/v1/autoconfig ###

_Retrieve the automatic generated tenside configuration._

#### Response ####

php_cli:

  * type: string
  * description: The PHP interpreter to run on command line.

php_cli_arguments:

  * type: string
  * description: Command line arguments to add.


### `GET` /api/v1/selftest ###

_Retrieve the results of all tests._

Retrieve the results of all tests.

#### Response ####

results[]:

  * type: object
  * description: The test results.

results[][name]:

  * type: string
  * description: The name of the test

results[][state]:

  * type: choice
  * description: The test result state.

results[][message]:

  * type: string
  * description: The detailed message of the test result.

results[][explain]:

  * type: string
  * description: Optional description that could hint any problems and/or explain the error further.



# tasks #

### `GET` /api/v1/tasks ###

_Retrieve the task list._

Retrieve the task list.

#### Response ####

<task-id>[]:

  * type: object

<task-id>[][id]:

  * type: string
  * description: The task id.

<task-id>[][type]:

  * type: string
  * description: The type of the task.


### `POST` /api/v1/tasks ###

_Queue a task in the list._

Queue a task in the list.

#### Response ####

status:

  * type: string
  * description: OK on success

task:

  * type: string
  * description: The id of the created task.


### `GET` /api/v1/tasks/run ###

_Starts the next pending task if any._

Starts the next pending task if any.

#### Response ####

status:

  * type: string
  * description: OK on success

type:

  * type: string
  * description: The type of the started task.

task:

  * type: string
  * description: The id of the started task.


### `DELETE` /api/v1/tasks/{taskId} ###

_Remove a task from the list._

Remove a task from the list.

#### Requirements ####

**taskId**

  - Requirement: [a-z0-9]+
  - Type: string
  - Description: The id of the task to remove.

#### Response ####

status:

  * type: string
  * description: OK on success


### `GET` /api/v1/tasks/{taskId} ###

_Retrieve the given task task._

Retrieve the given task task.

#### Requirements ####

**taskId**

  - Requirement: [a-z0-9]+
  - Type: string
  - Description: The id of the task to retrieve.

#### Filters ####

offset:

  * DataType: int
  * Description: If present, the output will be returned from the given byte offset.

#### Response ####

status:

  * type: string
  * description: The task status.

type:

  * type: string
  * description: The task type.

output:

  * type: string
  * description: The command line output of the task.
