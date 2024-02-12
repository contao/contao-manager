import MigrationView from '../../../src/components/views/MigrationView.vue'

describe('<MigrationView />', () => {
    it('renders', () => {
        const body = {
            type: 'schema',
            operations: [
                {
                    status: 'pending',
                    name: "ALTER TABLE ti_pdff_positions ADD type VARCHAR(8) DEFAULT 'text' NOT NULL, ADD bartype VARCHAR (12) DEFAULT 'QRCODE, ' NOT NULL, ADD picsize VARCHAR(64) DEFAULT '' NOT NULL, ADD textcolor VARCHAR(8) DEFAULT '' NOT NULL, ADD texttransform VARCHAR (32) DEFAULT '' NOT NULL, ADD pictype VARCHAR(8) DEFAULT 'file' NOT NULL, ADD picture BINARY (16) DEFAULT NULL, ADD pictag VARCHAR(64) DEFAULT 'file' NOT NULL, ADD arsize VARCHAR(2) DEFAULT '2' NOT NULL"},
            ],
            status: 'complete'
        }

        cy.intercept('GET', 'http://localhost:8000/api/contao/database-migration', { statusCode: 200, body }).as('getMigrations')
        cy.intercept('DELETE', 'http://localhost:8000/api/contao/database-migration', { statusCode: 200 }).as('deleteMigrations')

        cy.mount(MigrationView)

        cy.wait(['@getMigrations', '@deleteMigrations']);

        const fields = [
            'ti_pdff_positions.type',
            'ti_pdff_positions.bartype',
            'ti_pdff_positions.picsize',
            'ti_pdff_positions.textcolor',
            'ti_pdff_positions.texttransform',
            'ti_pdff_positions.pictype',
            'ti_pdff_positions.picture',
            'ti_pdff_positions.pictag',
            'ti_pdff_positions.arsize',
        ];

        cy.get('.console-operation__title').each((el, index) => {
            cy.wrap(el).should('contains.text', fields[index]);
        })
    })
})
