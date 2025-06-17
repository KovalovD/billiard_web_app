// resources/js/Components/ui/index.ts

// Form components
import Button from './button/Button.vue';
import Input from './form/Input.vue';
import Label from './form/Label.vue';
import Select from './form/Select.vue';
import SelectContent from './form/SelectContent.vue';
import SelectItem from './form/SelectItem.vue';
import SelectTrigger from './form/SelectTrigger.vue';
import SelectValue from './form/SelectValue.vue';
import Textarea from './form/Textarea.vue';
// Layout components
import Card from './layout/Card.vue';
import CardHeader from './layout/CardHeader.vue';
import CardTitle from './layout/CardTitle.vue';
import CardDescription from './layout/CardDescription.vue';
import CardContent from './layout/CardContent.vue';
import CardFooter from './layout/CardFooter.vue';

// Feedback components
import Modal from './feedback/Modal.vue';
import Toast from './feedback/Toast.vue';
import Spinner from './feedback/Spinner.vue';

export {default as Dialog} from './dialog/Dialog.vue';
export {default as DialogContent} from './dialog/DialogContent.vue';
export {default as DialogHeader} from './dialog/DialogHeader.vue';
export {default as DialogTitle} from './dialog/DialogTitle.vue';
export {default as DialogDescription} from './dialog/DialogDescription.vue';

// Data components
export {default as DataTable} from './data-table/DataTable.vue';

// Export both as default and named exports to support both import styles
export {
    // Form components
    Button,
    Input,
    Label,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Textarea,

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
    Spinner
};

// Also provide default export for flexibility
export default {
    // Form components
    Button,
    Input,
    Label,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Textarea,

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
    Spinner
};
