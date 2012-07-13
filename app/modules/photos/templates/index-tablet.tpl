{extends file="findExtends:modules/photos/templates/index.tpl"}

{block name="navList"}
  
  <!-- Static HTML mockup -->
  
  <div class="photo-albums">
  
    <div class="album">
        <a href="album?id=nasa&amp;_b=%5B%7B%22t%22%3A%22Photos%22%2C%22lt%22%3A%22Photos%22%2C%22p%22%3A%22index%22%2C%22a%22%3A%22%22%7D%5D">
        <span class="album-cover"><img src="http://farm5.staticflickr.com/4146/5037071883_e3109b7543_q.jpg" width="150" height="150" alt="" /></span>
        <span class="album-title">NASA Image of the Day</span>
        <span class="smallprint serviceicon flickr">flickr | 20 photos</span>
        </a>
    </div>
  
    <div class="album">
        <a href="album?id=featured&_b=%5B%7B%22t%22%3A%22Photos%22%2C%22lt%22%3A%22Photos%22%2C%22p%22%3A%22index%22%2C%22a%22%3A%22%22%7D%5D">
            <span class="album-cover"><img src=" https://lh5.googleusercontent.com/-2ne1Rpbeyos/T8MM2axAYtI/AAAAAAAAC-I/fOvGPxVeucE/s150-c/cityscapes_dubai_DSC_8060_sRGB_y.jpg" width="150" height="150" alt="" /></span>
        <span class="album-title">Picasa Featured Photos</span>
        <span class="smallprint serviceicon picasa">Picasa | 50 photos</span>
        </a>
    </div>
  
    <div class="album">
        <a href="album?id=villanova&_b=%5B%7B%22t%22%3A%22Photos%22%2C%22lt%22%3A%22Photos%22%2C%22p%22%3A%22index%22%2C%22a%22%3A%22%22%7D%5D">
        <span class="album-cover"><img src="http://farm8.staticflickr.com/7042/7139687009_34099a02a5_q.jpg" width="150" height="150" alt="" /></span>
        <span class="album-title">Villanova Flickr</span>
        <span class="smallprint serviceicon flickr">flickr | 1216 photos</span>
        </a>
    </div>
  
    <div class="album">
        <a href="album?id=featured&_b=%5B%7B%22t%22%3A%22Photos%22%2C%22lt%22%3A%22Photos%22%2C%22p%22%3A%22index%22%2C%22a%22%3A%22%22%7D%5D">
            <span class="album-cover"><img src=" https://lh5.googleusercontent.com/-2ne1Rpbeyos/T8MM2axAYtI/AAAAAAAAC-I/fOvGPxVeucE/s150-c/cityscapes_dubai_DSC_8060_sRGB_y.jpg" width="150" height="150" alt="" /></span>
        <span class="album-title">Picasa Featured Photos</span>
        <span class="smallprint serviceicon picasa">Picasa | 50 photos</span>
        </a>
    </div>
  
 
  </div> <!-- class="photo-albums" -->
  
  <!-- End static HTML mockup -->
  
{/block}
