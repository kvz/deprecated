class Orm < ActiveRecord::Base
  set_primary_key "uuid"
  belongs_to :type
  validates_presence_of :source
  include UUIDHelper

  def parse_source
    typePatterns = {
      :cakephp => {
        :defin => /^\s*[Cc]lass\s+([A-Z][A-Za-z0-9_]+)\s+[Ee][Xx][Tt][Ee][Nn][Dd][Ss]\s+[A-Z][A-Za-z0-9_]+Model\s*\{(.+)\}/sm,
        :model => /['"]([A-Z][A-Za-z0-9_]+)['"]/,
        :child => /(belongsTo)\s+=\s+(array\s*\([^;]+)/,
        :paren => /(hasMany|hasOne|hasAndBelongsToMany)\s+=\s+(array\s*\(?<body>[^;]+)/,
      }
    }
    
    parsed = {}

    # Split source into models per type
    typeModels = {}
    typePatterns.each do |type, patterns|
      result = source.scan(patterns[:defin])
      if result.size then
        typeModels[type] = result
      end
    end
    # Order languages by modelcount
    typeModels.sort_by {|models| models.size}

    # Process hasbtm properties
    typeModels.each do |type, models|
      patterns = typePatterns[type]
      models.each do |modelcol|
        modelName, modelSource = modelcol
        parsed[modelName] = {}
        ['child','paren'].each do |relation|
          if source = modelSource.match(patterns[:child])
            parsed[modelName][:child] = source[2].scan(patterns[:model])
          end
        end
      end
    end


   return parsed.inspect

  end

  def build_graph()
    if (!self.uuid)
      before_create
    end
    # http://github.com/glejeune/Ruby-Graphviz/blob/master/examples/sample01.rb
    # http://www.omninerd.com/articles/Automating_Data_Visualization_with_Ruby_and_Graphviz
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
