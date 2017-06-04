# Export Bundle

A simple way to export data from specific entity to CSV file.

This bundle provide command to configure the exporter for each entity. Just execute the command and give the entity name as "AppBundle:Test".

Update Routing
----
    medooch_export:
        resource: "@ExportBundle/Controller"
        type: annotation
        prefix:   /export
        
Manual Configuration
----
    // app/config/config_dev.yml
    export:
        entities:
            test: # this key must be used at the route parameter as {entity}
                class: 'AppBundle\Entity\User'
                query:
                    join:
                        - 'e.relation,r'
                    select:
                        - 'e.id'
                        - 'r.id'
                        # others fields
                    where:
                        - 'e.id IS NOT NULL'
                        # others conditions
                    orderBy:
                        - 'e.id,asc'
                        - 'r.id,desc'
                    groupBy:
                        - 'e.id'

Usage
----
##### AutoConfigure
        bin/console medooch:export:configure
##### Twig View    
        <a href="{{ path('medooch_export_csv', {'entity' : 'test'}) }}">Export to csv</a>