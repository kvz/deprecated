[Librato](http://addons.heroku.com/librato) is an [add-on](http://addons.heroku.com) for collecting,
understanding, and acting on the metrics that matter to you.

Librato is a complete solution for monitoring and analyzing the
metrics that impact your business at all levels of the stack. It
provides
everything you need to visualize, analyze, and actively alert on the
metrics that matter to you. With drop-in support for [Rails
3.x][rails-gem], [JVM-based applications][coda-backend], and
[other languages][lang-bindings] you'll have metrics streaming into
Librato in minutes. From there you can build custom charts/dashboards,
annotate them with one-time events, and set threshold-based alerts.
Collaboration is supported through multi-user access, private dashboard
links, PNG chart snapshots, and seamless integration with popular
third-party services like PagerDuty, Campfire, and HipChat.

Additionally Librato is first-and-foremost a platform whose complete capabilites are
programatically accessible via a [RESTful API][api-docs] with bindings
available for [a growing host of languages][lang-bindings] including
Ruby, Python, Java, Go, Clojure, Node.js, etc.

## Provisioning the add-on

Librato can be attached to a Heroku application via the CLI:

<div class="callout" markdown="1">
A list of all plans available can be found [here](http://addons.heroku.com/librato).
</div>

    :::term
    $ heroku addons:add librato
    -----> Adding librato to sharp-mountain-4005... done, v18 (free)

Once Librato has been added a `LIBRATO_USER` and `LIBRATO_TOKEN`
settings will be available in the app configuration and will contain the
credentials needed to authenticate to the [Librato API][api-docs]. This can be confirmed using the `heroku config:get` command.

    :::term
    $ heroku config:get LIBRATO_USER
    app123@heroku.com

After installing Librato you will need to explicitly set a value for `LIBRATO_SOURCE` in the app configuration.
`LIBRATO_SOURCE` informs the Librato service that the metrics coming from each of your dynos belong to the
same application.

    :::term
    $ heroku config:add LIBRATO_SOURCE=myappname

The value of `LIBRATO_SOURCE` must be composed of characters in the set
`A-Za-z0-9.:-_` and no more than 255 characters long. You should use 
a permanent name, as changing it in the future will cause
your historical metrics to become disjoint.

## Using with Rails 3.x

Ruby on Rails applications will need to add the following entry into their `Gemfile` specifying the Librato client library.

    :::ruby
    gem 'librato-rails'

Update application dependencies with bundler.

    :::term
    $ bundle install

### Automatic Instrumentation

After installing the `librato-rails` gem and deploying your app you
will see a number of metrics appear automatically in your Librato account.
These are powered by [ActiveSupport::Notifications][ASN] and track
request performance, sql queries, mail handling, etc.

Built-in performance metric names will start with either `rack` or `rails`,
depending on the level they are being sampled from. For example:
`rails.request.total` is the total number of requests rails has received
each minute.

### Custom Instrumentation

The power of Librato really starts to shine when you start adding your
own custom instrumentation to the mix. Tracking anything in your
application that interests you is easy Librato. There are
basically four instrumentation primitives available:

#### increment

Use for tracking a running total of something _across_ requests,
examples:

    # increment the 'sales_completed' metric by one
    Librato.increment 'sales_completed'
    
    # increment by five
    Librato.increment 'items_purchased', :by => 5
    
    # increment with a custom source
    Librato.increment 'user.purchases', :source => user.id
    
Other things you might track this way: user signups, requests of a
certain type or to a certain route, total jobs queued or processed,
emails sent or received.

Note that `increment` is primarily used for tracking the rate of
occurrence of some event. Given this `increment` metrics are _continuous
by default_ i.e. after being called on a metric once they will report on
every interval, reporting zeros for any interval when increment was not
called on the metric.

Especially with custom sources you may want the opposite behavior -
reporting a measurement only during intervals where `increment` was
called on the metric:

    # report a value for 'user.uploaded_file' only during non-zero
intervals
    Librato.increment 'user.uploaded_file', :source => user.id,
:sporadic => true

#### measure

Use when you want to track an average value _per_-request. Examples:

    Librato.measure 'user.social_graph.nodes', 212

#### timing

Like `Librato.measure` this is per-request, but specialized for timing
information:

    Librato.timing 'twitter.lookup.time', 21.2

The block form auto-submits the time it took for its contents to execute
as the measurement value:

    Librato.timing 'twitter.lookup.time' do
      @twitter = Twitter.lookup(user)
    end

#### group

There is also a grouping helper, to make managing nested metrics easier.
So this:

    Librato.measure 'memcached.gets', 20
    Librato.measure 'memcached.sets', 2
    Librato.measure 'memcached.hits', 18
    
Can also be written as:

    Librato.group 'memcached' do |g|
      g.measure 'gets', 20
      g.measure 'sets', 2
      g.measure 'hits', 18
    end

Symbols can be used interchangably with strings for metric names.

## Monitoring & Logging

Stats and the current state of Librato can be displayed via the CLI.

    :::term
    $ heroku librato:command
    example output

Librato activity can be observed within the Heroku log-stream by [[describe add-on logging recognition, if any]].

    :::term
    $ heroku logs -t | grep 'librato pattern'

## Dashboard

<div class="callout" markdown="1">
For more information on the features available within the Librato dashboard please see the docs at [mysite.com/docs](mysite.com/docs).
</div>

The Librato dashboard allows you to [[describe dashboard features]].

![Librato Dashboard](http://i.imgur.com/FkuUw.png "Librato Dashboard")

The dashboard can be accessed via the CLI:

    :::term
    $ heroku addons:open librato
    Opening librato for sharp-mountain-4005…

or by visiting the [Heroku apps web interface](http://heroku.com/myapps) and selecting the application in question. Select Librato from the Add-ons menu.

![Librato Add-ons Dropdown](http://f.cl.ly/items/1B090n1P0d3W0I0R172r/addons.png "Librato Add-ons Dropdown")

## Troubleshooting

It may take 2-3 minutes for the first results to show up in your Metrics
account after you have deployed your app and the first request has been received.

Note that if Heroku idles your application, measurements will not be sent
until it receives another request and is restarted. If you see
intermittent gaps in your measurements during periods of low traffic
this is the most likely cause.

## Migrating between plans

<div class="note" markdown="1">Application owners should carefully manage the migration timing to ensure proper application function during the migration process.</div>

[[Specific migration process or any migration tips 'n tricks]]

Use the `heroku addons:upgrade` command to migrate to a new plan.

    :::term
    $ heroku addons:upgrade librato:newplan
    -----> Upgrading librato:newplan to sharp-mountain-4005... done, v18 ($49/mo)
           Your plan has been updated to: librato:newplan

## Removing the add-on

Librato can be removed via the  CLI.

<div class="warning" markdown="1">This will destroy all associated data and cannot be undone!</div>

    :::term
    $ heroku addons:remove librato
    -----> Removing librato from sharp-mountain-4005... done, v20 (free)

Before removing Librato data can be exported through the [Librato
API][api-docs].

## Support

All Librato support and runtime issues should be submitted via on of the [Heroku Support channels](support-channels). Any non-support related issues or product feedback is welcome at [[your channels]].

[api-docs]: http://dev.librato.com/v1/metrics
[lang-bindings]: http://support.metrics.librato.com/knowledgebase/articles/122262-language-bindings
[rails-gem]: https://github.com/librato/librato-rails
[coda-metrics]: http://metrics.codahale.com/
[coda-backend]: https://github.com/librato/metrics-librato
