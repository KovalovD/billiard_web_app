// resources/js/Components/ui/index.ts

// Form components
import Button from './button/Button.vue';
import Input from './form/Input.vue';
import Label from './form/Label.vue';
import Textarea from './form/Textarea.vue';

// Select components (new location)
import Select from './select/Select.vue';
import SelectContent from './select/SelectContent.vue';
import SelectGroup from './select/SelectGroup.vue';
import SelectItem from './select/SelectItem.vue';
import SelectLabel from './select/SelectLabel.vue';
import SelectSeparator from './select/SelectSeparator.vue';
import SelectTrigger from './select/SelectTrigger.vue';
import SelectValue from './select/SelectValue.vue';

import Accordion from './accordion/Accordion.vue';
import AccordionItem from './accordion/AccordionItem.vue';
import AccordionTrigger from './accordion/AccordionTrigger.vue';
import AccordionContent from './accordion/AccordionContent.vue';
// Layout components
import Card from './layout/Card.vue';
import CardHeader from './layout/CardHeader.vue';
import CardTitle from './layout/CardTitle.vue';
import CardDescription from './layout/CardDescription.vue';
import CardContent from './layout/CardContent.vue';
import CardFooter from './layout/CardFooter.vue';
import Alert from './alert/Alert.vue';
import AlertDescription from './alert/AlertDescription.vue';
import AlertTitle from './alert/AlertTitle.vue';
import Badge from './badge/Badge.vue';

// Switch component
import Switch from './switch/Switch.vue';

// Dialog Footer
import DialogFooter from './dialog/DialogFooter.vue';
// Feedback components
import Modal from './feedback/Modal.vue';
import Toast from './feedback/Toast.vue';
import Spinner from './feedback/Spinner.vue';

export {default as Checkbox} from './checkbox/Checkbox.vue';

export {default as Dialog} from './dialog/Dialog.vue';
export {default as DialogContent} from './dialog/DialogContent.vue';
export {default as DialogHeader} from './dialog/DialogHeader.vue';
export {default as DialogTitle} from './dialog/DialogTitle.vue';
export {default as DialogDescription} from './dialog/DialogDescription.vue';

// Data components
export {default as DataTable} from './data-table/DataTable.vue';

// Export both as default and named exports to support both import styles
export {
    Button,
    Input,
    Label,
    Textarea,
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectSeparator,
    SelectTrigger,
    SelectValue,
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
    CardFooter,
    Alert,
    Badge,
    AlertDescription,
    AlertTitle,
    Switch,
    DialogFooter,
    Modal,
    Toast,
    Spinner,
    Accordion,
    AccordionItem,
    AccordionTrigger,
    AccordionContent
};

// Also provide default export for flexibility
export default {
    // Form components
    Button,
    Input,
    Label,
    Textarea,

    // Select components
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectSeparator,
    SelectTrigger,
    SelectValue,

    // Layout components
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
    CardFooter,

    // Feedback components
    Modal,
    Toast,
    Spinner,
    Badge,
    Accordion,
    AccordionItem,
    AccordionTrigger,
    AccordionContent
};
