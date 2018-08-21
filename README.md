# image-cache
This script for caching external images on webserver

The server fetches images from external source, caches them locally and makes copies of those images of certain sizes.
So the user can have the following image directory structure, for example, on the server-side:

imagecache:
 - icons
 - preview
 - full
 - 1920-1080
 - original

The original images are in "original" directory.

On the client based on the location of the image on the screen custom javascript function determines image size - one of the four hardcoded in this example, and sends a request to the server:

`/image.php?size=preview&url=http://external-site.com/some-image.jpg`

The server checks if the picture is in the desired size cache, if it is not - in the "original", if not - loads it from the URL to "original" directory, makes a copy of it of the requested size and sends it back to the client.

User can delete all the cache directories, it will start to fill them up again and the client will not notice anything.
