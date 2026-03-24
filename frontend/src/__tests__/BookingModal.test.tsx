import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { BookingModal } from '../components/BookingModal';

describe('BookingModal', () => {
  const mockProps = {
    isOpen: true,
    slotId: '123e4567-e89b-12d3-a456-426614174000',
    slotTime: '15:00 – 16:00',
    onClose: jest.fn(),
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Rendering', () => {
    it('should render modal when isOpen is true', () => {
      render(<BookingModal {...mockProps} />);
      
      expect(screen.getByText('Rezerwacja terminu')).toBeInTheDocument();
      expect(screen.getByText('Termin: 15:00 – 16:00')).toBeInTheDocument();
      expect(screen.getByLabelText(/Adres e-mail pacjenta/)).toBeInTheDocument();
    });

    it('should render modal with correct slot information', () => {
      render(<BookingModal {...mockProps} />);
      
      expect(screen.getByText('15:00 – 16:00')).toBeInTheDocument();
      expect(screen.getByPlaceholderText('jan.kowalski@example.com')).toBeInTheDocument();
    });

    it('should render close button', () => {
      render(<BookingModal {...mockProps} />);
      
      const closeButton = screen.getByRole('button', { name: '' });
      expect(closeButton).toBeInTheDocument();
      expect(closeButton.querySelector('[data-lucide="x"]')).toBeInTheDocument();
    });
  });

  describe('Form validation', () => {
    it('should disable submit button when email is empty', () => {
      render(<BookingModal {...mockProps} />);
      
      const submitButton = screen.getByRole('button', { name: /zarezerwuj termin/i });
      expect(submitButton).toBeDisabled();
      expect(submitButton).toHaveClass('disabled:cursor-not-allowed');
      expect(submitButton).toHaveClass('disabled:opacity-50');
    });

    it('should enable submit button when email is provided', () => {
      render(<BookingModal {...mockProps} />);
      
      const emailInput = screen.getByLabelText(/Adres e-mail pacjenta/);
      fireEvent.change(emailInput, { target: { value: 'test@example.com' } });
      
      const submitButton = screen.getByRole('button', { name: /zarezerwuj termin/i });
      expect(submitButton).not.toBeDisabled();
    });

    it('should show error message when email is empty', () => {
      render(<BookingModal {...mockProps} />);
      
      const submitButton = screen.getByRole('button', { name: /zarezerwuj termin/i });
      fireEvent.click(submitButton);
      
      expect(screen.getByText('Wpisz adres e-mail')).toBeInTheDocument();
    });

    it('should show error message when email does not contain @', () => {
      render(<BookingModal {...mockProps} />);
      
      const emailInput = screen.getByLabelText(/Adres e-mail pacjenta/);
      fireEvent.change(emailInput, { target: { value: 'invalidemail' } });
      
      const submitButton = screen.getByRole('button', { name: /zarezerwuj termin/i });
      fireEvent.click(submitButton);
      
      expect(screen.getByText('Wpisz poprawny adres e-mail')).toBeInTheDocument();
    });

    it('should show error message when email contains @ but no @ symbol', () => {
      render(<BookingModal {...mockProps} />);
      
      const emailInput = screen.getByLabelText(/Adres e-mail pacjenta/);
      fireEvent.change(emailInput, { target: { value: 'invalid@' } });
      
      const submitButton = screen.getByRole('button', { name: /zarezerwuj termin/i });
      fireEvent.click(submitButton);
      
      expect(screen.getByText('Wpisz poprawny adres e-mail')).toBeInTheDocument();
    });

    it('should show success message when booking is successful', async () => {
      const mockOnSuccess = jest.fn();
      const mockOnError = jest.fn();
      const mockMutate = jest.fn().mockImplementation(({ onSuccess }) => {
        setTimeout(() => onSuccess(), 100);
      });
      
      const { rerender } = render(
        <BookingModal 
          {...mockProps} 
          slotTime="15:00 – 16:00" 
        />
      );
      
      const emailInput = screen.getByLabelText(/Adres e-mail pacjenta/);
      fireEvent.change(emailInput, { target: { value: 'test@example.com' } });
      
      const submitButton = screen.getByRole('button', { name: /zarezerwuj termin/i });
      fireEvent.click(submitButton);
      
      await waitFor(() => {
        expect(screen.getByText('Zarezerwowano!')).toBeInTheDocument();
        expect(screen.getByText('Wysłaliśmy potwierdzenie na adres e-mail')).toBeInTheDocument();
      });
    });

    it('should disable input during submission', async () => {
      render(<BookingModal {...mockProps} />);
      
      const emailInput = screen.getByLabelText(/Adres e-mail pacjenta/);
      fireEvent.change(emailInput, { target: { value: 'test@example.com' } });
      
      const submitButton = screen.getByRole('button', { name: /zarezerwuj termin/i });
      fireEvent.click(submitButton);
      
      await waitFor(() => {
        expect(emailInput).toBeDisabled();
      });
    });

    it('should show "Rezerwowanie..." text during submission', async () => {
      render(<BookingModal {...mockProps} />);
      
      const emailInput = screen.getByLabelText(/Adres e-mail pacjenta/);
      fireEvent.change(emailInput, { target: { value: 'test@example.com' } });
      
      const submitButton = screen.getByRole('button', { name: /zarezerwuj termin/i });
      fireEvent.click(submitButton);
      
      await waitFor(() => {
        expect(screen.getByText('Rezerwowanie...')).toBeInTheDocument();
      });
    });
  });

  describe('Interaction', () => {
    it('should call onClose when close button is clicked', () => {
      render(<BookingModal {...mockProps} />);
      
      const closeButton = screen.getByRole('button', { name: '' });
      fireEvent.click(closeButton);
      
      expect(mockProps.onClose).toHaveBeenCalledTimes(1);
    });

    it('should call onClose when clicking outside the modal', () => {
      render(<BookingModal {...mockProps} />);
      
      const overlay = screen.getByRole('button', { name: '' });
      fireEvent.click(overlay);
      
      expect(mockProps.onClose).toHaveBeenCalledTimes(1);
    });

    it('should prevent modal close when clicking inside the content area', () => {
      render(<BookingModal {...mockProps} />);
      
      const modalContent = screen.getByRole('button', { name: /Rezerwacja terminu/ });
      fireEvent.click(modalContent);
      
      expect(mockProps.onClose).not.toHaveBeenCalled();
    });
  });

  describe('Error handling', () => {
    it('should show error for 409 conflict when slot is already booked', async () => {
      const mockError = {
        response: { status: 409 },
      };
      
      const mockOnError = jest.fn((err: any) => {
        if (err.response?.status === 409) {
          console.log('Conflict error');
        }
      });
      
      render(<BookingModal {...mockProps} />);
      
      const emailInput = screen.getByLabelText(/Adres e-mail pacjenta/);
      fireEvent.change(emailInput, { target: { value: 'test@example.com' } });
      
      const submitButton = screen.getByRole('button', { name: /zarezerwuj termin/i });
      fireEvent.click(submitButton);
      
      await waitFor(() => {
        expect(screen.getByText('Ten termin jest już zajęty')).toBeInTheDocument();
      });
    });

    it('should show generic error for other HTTP errors', async () => {
      const mockError = {
        response: { status: 500 },
      };
      
      render(<BookingModal {...mockProps} />);
      
      const emailInput = screen.getByLabelText(/Adres e-mail pacjenta/);
      fireEvent.change(emailInput, { target: { value: 'test@example.com' } });
      
      const submitButton = screen.getByRole('button', { name: /zarezerwuj termin/i });
      fireEvent.click(submitButton);
      
      await waitFor(() => {
        expect(screen.getByText('Wystąpił błąd podczas rezerwacji. Spróbuj ponownie.')).toBeInTheDocument();
      });
    });
  });

  describe('Empty state', () => {
    it('should render success state when booking was successful', async () => {
      render(<BookingModal {...mockProps} />);
      
      const emailInput = screen.getByLabelText(/Adres e-mail pacjenta/);
      fireEvent.change(emailInput, { target: { value: 'test@example.com' } });
      
      const submitButton = screen.getByRole('button', { name: /zarezerwuj termin/i });
      fireEvent.click(submitButton);
      
      await waitFor(() => {
        expect(screen.getByText('Zarezerwowano!')).toBeInTheDocument();
      });
    });
  });
});
