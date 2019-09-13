OpenAgency WebService, Copyright(c) 2009, DBC

# Introduction

OpenAgency webservice and client


# License

DBC-Software Copyright ?. 2009, Danish Library Center, dbc as.

This library is Open source middleware/backend software developed and distributed
under the following licenstype:

GNU, General Public License Version 3. If any software components linked
together in this library have legal conflicts with distribution under GNU 3 it
will apply to the original license type.

Software distributed under the License is distributed on an "AS IS" basis,
WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
for the specific language governing rights and limitations under the
License.

Around this software library an Open Source Community is established. Please
leave back code based upon our software back to this community in accordance to
the concept behind GNU.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA


# Documentation
http://oss.dbc.dk/plone/services/open-agency

Use Doxygen to get code documentation

# Release information

This version of OpenAgency is based on OLS class library revision 117239 with the following exceptions:\
File pg_database_class.php is from revision 124699\
File pg_wrapper_class.php is from revision 124699\
File verbose_json_class.php is from revision 124699

# Getting Started

This project depends on a project still in subversion. After checkout from gitlab, 
run the script [`script/bootstrap`](script/bootstrap)
to retrieve this svn dependency:
```bash
$ ./script/bootstrap 
INFO: Checking out svn repo 'https://svn.dbc.dk/repos/php/OpenLibrary/class_lib/trunk' into directory './src/OLS_class_lib'
INFO: Svn repo 'https://svn.dbc.dk/repos/php/OpenLibrary/class_lib/trunk' checked out into directory './src/OLS_class_lib'
INFO: Svn info:
Revision: 122504
Last Changed Author: fvs
Last Changed Rev: 122080
Last Changed Date: 2018-10-02 15:59:39 +0200 (tir, 02 okt 2018)
```

As the name suggests, you can also run this script to update the contents. Changes to the external svn project is handled
as ordinary svn changes.

See the [script/README](script/README.md) for additional info about build scripts.

## Building

The project can be run "as is" in a properly configured Apache webserver, or you can build a docker image to test in.

To build the docker image, the `build-dockers.py` tool is used. 
In the root directory, use `scripts/build` or use the `build-dockers.py` script directly. 

## Test

Tests are based on the docker images.

To run a WebService image for ad-hoc tests, use `script/server`. This will start a docker instance of the service, 
using the supplied [docker-compose file](docker/compose/systemtest/docker-compose.yml). 

The service ports are allocated dynamically, but you can use the debug service to make it accessible 
on `localhost:8080` in a web browser.

To run a test that involves connecting to a database, use `script/test` or run the `run-system-test.sh` script directly. 
This will start the webservice, wait for it to be ready, and then perform a few verifications.

