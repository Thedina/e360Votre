##Issuance (Permitting) Module Specifications (Phase 1 Draft)

The Issuance Module allows the creation and completion of workflow dependancies. When all Conditions are met, it 
allows the issuance of a permit or other document. The document issued is customizable. The Permitting Module supports 
multiple chains of Conditions, and can accept files to satisfy Conditions.

###Types

The ability to configure one or more Types of Issuance.  For example, "Building Permit" and "Certificate of Occupancy". 
Each Type is identified by idController and idIssuanceType and stored in a single Table.

Each Type has a 1-to-many relationship with Templates.  Templates are HTML forms that are configured by Admins, and 
they have access to the Project variables via Keydict.  Templates can be saved to PDF.

Each Type has a 1-to-many relationship with Conditions.  When the Type's Conditions are met completely, the Type will
 fire an appropriate Trigger and make its documents available on a User facing endpoint.  The Template PDFs should be
  made available to the Trigger.

###Conditions

The ability to inform the Issuance Module about a given type of Condition and its Status.

The Module needs to be informed during init what conditions the workflow will use and satisfy.  It's possible to specify
a Module that will handle the Condition.  In some cases the Module will not be able to properly model the condition and 
in those cases the Condition tracking will need to happen manually (without the binding of the module).

```
$issuanceModule->setConditions(
    Condition::build('Submittal Items Accepted')->bindModule($this->getChild('submittals')),
    Condition::build('Fees Paid')->bindModule($this->getChild('fees')),
    Condition::build('Planning Approval')->setBool()
);
```

Each Condition maintains a Float of the Current Value and the Max Value.  The Values can be adjusted via the low level 
set() and increment() methods.  Since some conditions will be a boolean value rather than incremental, it's possible 
for the option to be set on the Condition.


```
$issuanceModule->getCondition('Submittal Items Accepted')
    ->set(Issuance::MAX, 2)
    ->increment(Issuance::CURRENT, 1);
```

It's possible to address a Condition outside of getCondition() if available.

Like other situations in the Workflow controllers, it should be possible for Admins to arbitrarily add Conditions 
through a UI.  These Conditions should be bindable to Modules that support Conditions.  In this case however, the 
Workflow will not be able to set/increment Conditions arbitrarily and will instead rely on the built-in Module 
Condition updaters.

##Phase 2 (Draft)

Phase 2 incorporates more advanced functionality that isn't part of the initial requirements.

- [ ] Templates support lists of items.
- [ ] Status of Conditions can be read as a Foreign Keydict.
- [ ] Ability to define and send Email templates from Issuance Admin Panels. (needs more thought)
