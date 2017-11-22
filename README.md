Google Analytics Visitors Bundle
===============

A bundle for Symfony2, that retrieves visit statistics from Google Analytics.

1) Installation
---------------------

AnalyticsBundle can be installed via Composer.
You can find this bundle on packagist: https://packagist.org/packages/arcanacode/google-analytic

<pre>
<code>
// composer.json
{
    // ...
    require: {
        // ..
        "arcanacode/google-analytic": "dev-master"

    }
}
</code>
</pre>

Then, you can install the new dependencies by running Composer's update command from the directory where your composer.json file is located:

<pre>
<code>
    php composer.phar update
</code>
</pre>

You have to add this bundle to `AppKernel.php` register bundles method, so that Symfony can use it.
<pre>
// in AppKernel::registerBundles()
$bundles = array(
    // ...
    new Arcana\AnalyticBundle\ArcanaAnalyticBundle(),
);
</pre>

In your `config.yml` you must add this bundle to assetic.

<pre>
assetic:
    //..
    bundles:        [ ArcanaAnalyticBundle ]
</pre>

In your `parameters.yml` you must fill these parameters.

<pre>
aa_default:
    aa_enabled: true
    aa_domain: yourdomain.com
    aa_domain_code: XX-00000000-0
    aa_usermail: 'your-mail@gmail.com'
    aa_userpass: 'password'
    aa_token: null
</pre>

* `aa_domain` is mandatory parameter - Your domain registered in Google Analytics.
* `aa_domain_code` is mandatory parameter - Your domain code given by Google Analytics.
* `aa_usermail` Your account email used for authentication. Mandatory if no token present.
* `aa_userass` Your account password used for authentication. Mandatory if no token present.
* `aa_token` your authentication token if no email and password provided

This will be for the default domain, but you can add multiple domain by using pattern "aa_newdomain"
<pre>
aa_default:
    aa_enabled: true
    aa_domain: yourdomain.com
    aa_domain_code: XX-00000000-0
    aa_usermail: 'your-mail@gmail.com'
    aa_userpass: 'password'
    aa_token: null
aa_domainone:
    aa_enabled: true
    aa_domain: yourdomain2.com
    aa_domain_code: XX-11111111-1
    aa_usermail: 'your-mail-two@gmail.com'
    aa_userpass: 'password2'
    aa_token: null
</pre>

2) Usage
----------------------------------

You can use this bundle to generate Google Analytics statistics in your desirable view by rendering controller from the view

<pre>
<code>
{% render controller('ArcanaAnalyticBundle:Default:index') %}
</code>
</pre>

This example will use the default domain from 'parameters.yml', but if you want to specify some other domain from parameters, you just pass variable 'domain' to the controller like this:
<pre>
<code>
{% render controller('ArcanaAnalyticBundle:Default:index', {domain:'domainone'}) %}
</code>
</pre>
Remember - the 'domain' variable must be without 'aa_' - if there is in your parameters.yml 'aa_supersite', you will pass only 'supersite'!


Also You must add javascript block to your view.

<pre>
<code>
{% block javascripts %}
    {{ parent() }}
    {{ include('ArcanaAnalyticBundle:Default:javascripts.html.twig') }}
{% endblock %}
</code>
</pre>

If You don't have jquery-ui stylesheets already on Your site, You must connect it

If You have already jquery-ui javascript with datepicker on Your site, please remove `<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>` from `ArcanaAnalyticBundle:Default:javascripts.html.twig`
