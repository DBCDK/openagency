OpenAgency WebService, Copyright(c) 2009, DBC

Introduction
------------

OpenAgency webservice and client


License
-------
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


Documentation
-------------
http://oss.dbc.dk/plone/services/open-agency

Use Doxygen to get code documentation


Build
-----

To fetch The Needed OLS_class_lib files run the build.sh script. this script also builds 
the opensearch-webservice.tar.gz file needed for the docker build. 

```bash
./build.sh
(cd docker; docker build -t openagency:devel . )
docker run -ti -p 8080:80 --env-file=boble.env openagency:devel
```

Development
-----------

To start the docker with the php files just use 
```bash
./build.sh ; pushd docker ; docker build -t openagency:devel . ; popd ; docker run --rm --env-file test.env -ti -p 8080:80 -v {PWD}:/var/www/html/openagency --name=oa openagency:devel 
```

Or do a rebuild of the image  and make a clean start 
```bash
./build.sh ; pushd docker ; docker build -t openagency:devel . ; popd ; docker run --rm --env-file test.env -ti -p 8080:80 --name=oa openagency:devel
```

