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

TBD