class Orm < ActiveRecord::Base
  set_primary_key "uuid"
  belongs_to :type
  validates_presence_of :source
  include UUIDHelper

  def parse_source
    typePatterns = {
      :cakephp => {
        :models => /^\s*[Cc]lass[ \t]+(A-Z[A-Za-z0-9_]+)[ \t]+[Ee][Xx][Tt][Ee][Nn][Dd][Ss][ \t]+(A-Z[A-Za-z0-9_]+Model)/sm,
        :proper => /(RELATION)\s+=\s+(array\s*\([^;]+)/,
        :parent => 'belongsTo',
        :child  => 'hasMany|hasOne|hasAndBelongsToMany'
      }
    }

    models = {}
    typePatterns.each do |type, patterns|
       models[:type] = self.source.scan(patterns[:models])
    end

   return models.inspect

  end

  def build_graph()
    if (!self.uuid)
      before_create
    end
    ext = 'png'
    url = '/graphs/' + self.uuid + '.' + ext
    file = File.join(RAILS_ROOT, 'public') + url

    require 'graphviz'

    # Create a new graph
    g = GraphViz.new( :G, :type => :digraph )

    # Create two nodes
    hello = g.add_node( "Hello" )
    world = g.add_node( "World" )

    # Create an edge between the two nodes
    g.add_edge( hello, world )

    # Generate output image
    #g.output( :png => "hello_world.png" )
    g.output( "output" => ext, :file => file)
    
    self.file = file
    self.url = url
    self.save!
  end
end
