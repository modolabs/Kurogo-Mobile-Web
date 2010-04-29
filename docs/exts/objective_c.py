# a protocol directive that is identical to the builtin class directive
import sphinx.directives.desc as desc
from sphinx.util.compat import directive_dwim
from sphinx import addnodes
from docutils import nodes
import re

# the various patterns we will try to match
method_prefix_re = re.compile(r'^(\+|\-)\s*(.*)$')

# example = '(dffds)  '
return_type_re = re.compile(r'^\((.*?)\)\s*(.*)$')

# example = '+ firstPart: (NSNumber *)number'
name_type_var_re = re.compile(r'^(\w+\:)\s*\((.*?)\)\s*(\w+)\s*(.*)$')

a_word_re = re.compile('^(\w+)$')

method_name_part_re = re.compile('^(\w+\:)\s*(.*)$')


def setup(app):
   app.add_directive('objcmethod', directive_dwim(ObjCMethod))
   app.add_node(desc_objc_method_type, html=(visit_desc_objc_method_type, depart_desc_objc_method_type))
   app.add_node(desc_objc_method_name_part, html=(visit_desc_objc_method_name_part, depart_desc_objc_method_name_part))
   app.add_node(desc_objc_type, html=(visit_desc_objc_type, depart_desc_objc_type))
   app.add_node(desc_objc_param, html=(visit_desc_objc_param, depart_desc_objc_param))

class ObjCMethod(desc.DescDirective):

    def parse_signature(self, sig, signode):
        if method_prefix_re.match(sig):
            prefix, remaining = method_prefix_re.match(sig).groups()
            signode += desc_objc_method_type(prefix)
            
            print remaining
            if return_type_re.match(remaining):
                print 'has_prefix'
                self.has_types = True
                return_type, remaining = return_type_re.match(remaining).groups()
                print return_type
                self.return_type = remove_spaces(return_type)
                signode += desc_objc_type(self.return_type)
                self.types = []
                self.params = []
            else:
                self.has_types = False

            self.names = []
            if self.has_types:
                print 'has_types'
                print remaining
                while remaining:
                   print remaining
                   name, objc_type, param, remaining = name_type_var_re.match(remaining).groups()
                   print 'doh'
                   objc_type = remove_spaces(objc_type)
                   print name
                   self.names.append(name)
                   self.types.append(type)
                   self.params.append(param)
                   signode += desc_objc_method_name_part(name)
                   signode += desc_objc_type(objc_type)
                   signode += desc_objc_param(param, param)
            else:
                if a_word_re.match(remaining):
                    self.names.append(remaining)
                    signode += desc_objc_method_name_part(remaining)
                else:
                   while remaining:
                       name, remaining = method_name_part_re.match(remaining).groups()
                       self.names.append(name)
                       signode += desc_objc_method_name_part(name)

def remove_spaces(text):
    return text.replace(" ", "")

class ObjCParseError(Exception): pass

pointer_type_re = re.compile(r'^(.*?)(\*+)$')
class desc_objc_type(nodes.Part, nodes.Inline, nodes.TextElement):
    def __init__(self, type_name):
        if pointer_type_re.match(type_name):
            first_part, pointer_part = pointer_type_re.match(type_name).groups()
            type_name = first_part + ' ' + pointer_part
        super(desc_objc_type, self).__init__(type_name, type_name)

class desc_objc_param(nodes.Part, nodes.Inline, nodes.TextElement): pass

class desc_objc_method_type(nodes.Part, nodes.Inline, nodes.TextElement):
    def __init__(self, method_type):
        if method_type == '+' or method_type == '-':
            super(desc_objc_method_type, self).__init__(method_type, method_type)
        else:
            raise ObjCParseError('Can not determine if this a class method(+) or an instance method(-)')

class desc_objc_method_name_part(nodes.Part, nodes.Inline, nodes.TextElement):
    def __init__(self, method_name_part):
        if method_name_part != '':
            super(desc_objc_method_name_part, self).__init__(method_name_part, method_name_part)
        else:
            raise ObjCParseError('A part of the method name is empty')
     
def visit_desc_objc_method_type(self, node):
    self.body.append('<big>')

def depart_desc_objc_method_type(self, node):
    self.body.append(' </big>')


def visit_desc_objc_method_name_part(self, node):
    self.body.append(' <tt class="descname">')

def depart_desc_objc_method_name_part(self, node):
    self.body.append('</tt>')

def visit_desc_objc_type(self, node):
    self.body.append('<big>(</big>')

def depart_desc_objc_type(self, node):
    self.body.append('<big>)</big>')


def visit_desc_objc_param(self, node):
    self.body.append('<em>')

def depart_desc_objc_param(self, node):
    self.body.append('</em>')



