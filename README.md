Per-user access module
=================

**Author:**  Luke Hudson <luke@silverstripe.com>

This module allows a single user to 'own' a page, so that they have the ability
to control the access to that page in a user-by-user manner.

Installation
------------
Place this module in the root of your SilverStripe installation.  It should be enabled automatically.


Requirements
------------
Requires SilverStripe 2.4 or greater.


How it works
------------
CAN VIEW
--------
is granted if any of the following is TRUE:
- user is owner
- no owner is set and user is owner of the next page that has an owner set when bubbling up sitetree
- user has been granted per user view permission
otherwise permission is denied if any of the following conditions is TRUE:
- canView() on any decorator returns FALSE
- "CanViewType" directive is set to "Inherit" and any parent page return false for canView()
- "CanViewType" directive is set to "LoggedInUsers" and no user is logged in
- "CanViewType" directive is set to "OnlyTheseUsers" and user is not in the given groups
otherwise permission is grated

CAN EDIT
--------
is granted if any of the following is TRUE:
- user is owner
- no owner is set and user is owner of the next page that has an owner set when bubbling up sitetree
- user has been granted per user edit permission
otherwise permission is denied if any of the following conditions is TRUE:
- canEdit() on any decorator returns FALSE
- canView() return false
- "CanEditType" directive is set to "Inherit" and any parent page return false for canEdit()
- "CanEditType" directive is set to "LoggedInUsers" and no user is logged in or doesn't have the CMS_Access_CMSMAIN permission code
- "CanEditType" directive is set to "OnlyTheseUsers" and user is not in the given groups
otherwise permission is grated

CAN CREATE
----------
is granted if any of the following is TRUE:
- user is owner
- no owner is set and user is owner of the next page that has an owner set when bubbling up sitetree
- user has been granted per user create permission
otherwise permission is denied if any of the following conditions is TRUE:
- alternate CanAddChildren() on a decorator returns FALSE
- canEdit() is not granted
- There are no classes defined in {@link $allowed_children}
otherwise permission is grated

CAN PUBLISH
-----------
is granted if any of the following is TRUE:
- user is owner
- no owner is set and user is owner of the next page that has an owner set when bubbling up sitetree
- user has been granted per user publish permission
otherwise permission is denied if any of the following conditions is TRUE:
- canPublish() on any decorator returns FALSE
- canEdit() returns FALSE
otherwise permission is grated

CAN DELETE
----------
is granted if any of the following is TRUE:
- user is owner
- no owner is set and user is owner of the next page that has an owner set when bubbling up sitetree
- user has been granted per user delete permission
otherwise permission is denied if any of the following conditions is TRUE:
- canDelete() returns FALSE on any decorator
- canEdit() returns FALSE
- any descendant page returns FALSE for canDelete()
otherwise permission is grated

