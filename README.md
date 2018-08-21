# image-cache
Caching external images on webserver

The server caches images and makes copies of those images of certain sizes.
So the user can have the following directory structure on the server-side:

imagecache:
 - icons
 - preview
 - full
 - 1920-1080
 - original

The original images are in "original" directory
On the client javascript based on the location of the picture on the screen determines its size - one of the four hardcoded, and sends a request to the server:

`/image.php?size=preview&url=http://external-site.com/some-image.jpg`

The server checks if the picture is in the desired size cache, if it is not - in the original, if not - loads it from the URL, makes it of the requested size and sends it back to the client.
User can delete all the cache directories, it will start to fill them up again and the client will not notice anything.
